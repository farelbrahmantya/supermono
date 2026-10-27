<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mode Demo: truk simulasi (is_demo = 1) yang bergerak sendiri antar tahap, untuk presentasi.
 * - start(): isi riwayat 14 hari (7 hari pertama lebih lambat, 7 hari terakhir lebih cepat) + truk aktif
 * - tick(): dipanggil dari dashboard / Layar TV, memajukan tahap truk demo dan menambah truk baru
 * - clear(): hapus semua data demo
 * Data demo tidak pernah menyentuh database depo (new_sby / new_general).
 */
class Demo_model extends CI_Model {

    // Jumlah truk demo yang dijaga tetap antre di luar selama demo aktif
    const WAITING_TARGET = 4;
    // Jeda minimum antar tick (detik): dashboard & TV memanggil setiap 5 detik
    const TICK_SECONDS = 4;

    private $ready = NULL;
    private $zones = NULL;

    private $principals = array('ONE' => 'ONEU', 'WAN HAI' => 'WHLU', 'KMTC' => 'KMTU', 'CMA CGM' => 'CMAU');
    private $customers = array('PT Sinar Bahari', 'PT Mitra Logistik Nusantara', 'CV Jaya Abadi', 'PT Samudra Niaga', 'PT Karya Ekspor Jatim', 'PT Bintang Kargo');
    private $sizes = array('GP 20 / 8.60', 'HC 40 / 9.60', 'GP 40 / 8.60');

    // Tabel pendukung sudah ada (upgrade_07_dampak_demo.sql sudah dijalankan)
    public function ready() {
        if ($this->ready === NULL) {
            $this->ready = $this->db->table_exists('lasmono_settings') && $this->db->field_exists('is_demo', 'queue_transactions');
        }
        return $this->ready;
    }

    public function setting($name, $default = NULL) {
        $row = $this->db->get_where('lasmono_settings', array('name' => $name))->row();
        return $row ? $row->value : $default;
    }

    public function set_setting($name, $value) {
        $this->db->query("INSERT INTO lasmono_settings (name, value, updated_at) VALUES (?, ?, ?)
                          ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = VALUES(updated_at)",
                         array($name, (string) $value, date('Y-m-d H:i:s')));
    }

    public function active() {
        return $this->ready() && $this->setting('demo_active') === '1';
    }

    public function has_data() {
        return $this->ready() && $this->db->where('is_demo', 1)->count_all_results('queue_transactions') > 0;
    }

    public function start() {
        $this->clear();
        $this->_seed_history();
        $this->_seed_live();
        $this->set_setting('demo_active', '1');
        $this->set_setting('demo_last_tick', time());
    }

    public function stop() {
        $this->set_setting('demo_active', '0');
    }

    public function clear() {
        $this->db->query("DELETE s FROM sos_reports s JOIN queue_transactions q ON q.id = s.queue_id WHERE q.is_demo = 1");
        $this->db->where('is_demo', 1)->delete('queue_transactions');
        $this->set_setting('demo_active', '0');
    }

    // Majukan tahap truk demo sesuai durasi simulasi masing-masing
    public function tick() {
        if (!$this->ready()) {
            return;
        }
        $last = (int) $this->setting('demo_last_tick', 0);
        if (time() - $last < self::TICK_SECONDS) {
            return;
        }
        $active = $this->setting('demo_active') === '1';
        $queues = $this->db->where('is_demo', 1)
                           ->where_in('status', array('WAITING_GATE', 'IN_YARD', 'IN_SURVEY', 'AT_YARD'))
                           ->order_by('id', 'ASC')
                           ->get('queue_transactions')->result();
        if (!$active && empty($queues)) {
            return;
        }
        $this->set_setting('demo_last_tick', time());

        $now = time();
        $gate_in_done = FALSE;
        foreach ($queues as $q) {
            $since = strtotime($this->_stage_start($q));
            if ($now - $since < $this->_duration($q->id, $q->status, $q->activity)) {
                continue;
            }
            $stamp = date('Y-m-d H:i:s', $now);
            if ($q->status === 'WAITING_GATE') {
                // Gate In satu per satu (FIFO), seperti petugas sungguhan
                if ($gate_in_done) continue;
                $gate_in_done = TRUE;
                $this->_update($q->id, array('status' => 'IN_YARD', 'gate_in_at' => $stamp));
            } elseif ($q->status === 'IN_YARD' && in_array($q->activity, array('D', 'E'), TRUE)) {
                $update = array('status' => 'AT_YARD', 'yard_scan_at' => $stamp, 'current_zone_id' => $this->_zone_for($q->principal, $q->id));
                if (!$q->container_no) {
                    $update['container_no'] = $this->_container_no($q->principal, $q->id + 7);
                }
                $this->_update($q->id, $update);
            } elseif ($q->status === 'IN_YARD') {
                $this->_update($q->id, array('status' => 'IN_SURVEY', 'survey_start_at' => $stamp));
            } elseif ($q->status === 'IN_SURVEY') {
                $this->_update($q->id, array('status' => 'AT_YARD', 'yard_scan_at' => $stamp, 'current_zone_id' => $this->_zone_for($q->principal, $q->id)));
            } else {
                $this->_update($q->id, array('status' => 'COMPLETED', 'gate_out_at' => $stamp));
            }
        }

        // Truk baru datang selama demo aktif
        if ($active) {
            $waiting = 0;
            foreach ($queues as $q) {
                if ($q->status === 'WAITING_GATE') $waiting++;
            }
            if ($waiting < self::WAITING_TARGET && mt_rand(1, 100) <= 60) {
                $this->db->insert('queue_transactions', $this->_truck(time(), 'WAITING_GATE', array()));
            }
        }
    }

    private function _update($id, $data) {
        $this->db->where('id', $id)->update('queue_transactions', $data);
    }

    private function _stage_start($q) {
        $map = array('WAITING_GATE' => 'created_at', 'IN_YARD' => 'gate_in_at', 'IN_SURVEY' => 'survey_start_at', 'AT_YARD' => 'yard_scan_at');
        return $q->{$map[$q->status]};
    }

    // Durasi simulasi (detik) per truk & tahap: sebagian besar 0,5-3 menit, sekitar 15% lewat target
    private function _duration($id, $status, $activity) {
        $h = abs(crc32($id . '|' . $status));
        if ($status === 'WAITING_GATE') {
            return 40 + $h % 140;
        }
        if ($h % 100 < 15) {
            $limit = ($status === 'IN_YARD' && $activity === 'D') ? 600 : 300;
            return $limit + 20 + $h % 120;
        }
        return 30 + $h % 150;
    }

    private function _zones() {
        if ($this->zones === NULL) {
            $this->zones = $this->db->get('yard_zones')->result();
        }
        return $this->zones;
    }

    private function _zone_for($principal, $seed) {
        $match = array();
        foreach ($this->_zones() as $z) {
            if (isset($z->principal) && strtoupper((string) $z->principal) === strtoupper((string) $principal)) {
                $match[] = $z;
            }
        }
        if (empty($match)) {
            $match = $this->_zones();
        }
        return empty($match) ? NULL : $match[abs(crc32('z' . $seed)) % count($match)]->id;
    }

    private function _container_no($principal, $seed) {
        $prefix = isset($this->principals[$principal]) ? $this->principals[$principal] : 'DEMU';
        return $prefix . str_pad((string) (abs(crc32('c' . $seed)) % 10000000), 7, '0', STR_PAD_LEFT);
    }

    private function _pick($list, $seed) {
        return $list[abs(crc32((string) $seed)) % count($list)];
    }

    // Satu baris truk demo. $times: kolom waktu yang sudah terisi (timestamp UNIX)
    private function _truck($created, $status, $times) {
        static $serial = 0;
        $serial++;
        $seed = $created . '-' . $serial . '-' . mt_rand();
        $roll = abs(crc32('a' . $seed)) % 100;
        $activity = $roll < 35 ? 'Z' : ($roll < 60 ? 'A' : ($roll < 85 ? 'D' : 'E'));
        $principal = $this->_pick(array_keys($this->principals), 'p' . $seed);
        $targets = array('Z' => 15, 'A' => 15, 'D' => 15, 'E' => 10);

        $row = array(
            'queue_number'       => $this->_next_number($created),
            'driver_phone'       => '0899' . str_pad((string) (abs(crc32('h' . $seed)) % 10000000), 7, '0', STR_PAD_LEFT),
            'license_plate'      => $this->_pick(array('L', 'W', 'S', 'N', 'AG'), 'r' . $seed) . ' ' . (1000 + abs(crc32('n' . $seed)) % 9000) . ' ' . chr(65 + abs(crc32('x' . $seed)) % 26) . chr(65 + abs(crc32('y' . $seed)) % 26),
            'status'             => $status,
            'activity'           => $activity,
            'driver_activity'    => $activity,
            'principal'          => $principal,
            // Export: container baru dipilih Yardman saat muat
            'container_no'       => $activity === 'D' && empty($times['yard_scan_at']) ? NULL : $this->_container_no($principal, $seed),
            'container_size'     => $this->_pick($this->sizes, 's' . $seed),
            'customer'           => $this->_pick($this->customers, 'u' . $seed),
            'sla_target_minutes' => $targets[$activity],
            'created_at'         => date('Y-m-d H:i:s', $created),
            'is_demo'            => 1
        );
        foreach (array('gate_in_at', 'survey_start_at', 'yard_scan_at', 'gate_out_at') as $col) {
            $row[$col] = isset($times[$col]) ? date('Y-m-d H:i:s', $times[$col]) : NULL;
        }
        // Export & Repo Out tidak melalui survey
        if (in_array($activity, array('D', 'E'), TRUE)) {
            $row['survey_start_at'] = NULL;
            if ($status === 'IN_SURVEY') $row['status'] = 'IN_YARD';
        }
        if (!empty($times['yard_scan_at'])) {
            $row['current_zone_id'] = $this->_zone_for($principal, $seed);
        }
        return $row;
    }

    private function _next_number($created) {
        $prefix = 'DM-' . date('Ymd', $created) . '-';
        $last = $this->db->select('queue_number')->like('queue_number', $prefix, 'after')
                         ->order_by('queue_number', 'DESC')->limit(1)->get('queue_transactions')->row();
        $next = $last ? (int) substr($last->queue_number, -3) + 1 : 1;
        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    // Riwayat 14 hari: 7 hari pertama (sebelum perbaikan) lebih lambat, 7 hari terakhir lebih cepat
    private function _seed_history() {
        for ($day = 13; $day >= 0; $day--) {
            $slow  = $day >= 7;
            $count = $day === 0 ? 10 : 14 + mt_rand(0, 8);
            for ($i = 0; $i < $count; $i++) {
                if ($day === 0) {
                    // Hari ini: selesai dalam 4 jam terakhir
                    $created = time() - mt_rand(40, 240) * 60;
                } else {
                    $created = strtotime(date('Y-m-d', strtotime("-{$day} days")) . ' 07:00:00') + mt_rand(0, 10 * 3600);
                }
                $wait  = $slow ? mt_rand(15, 45) : mt_rand(4, 14);
                $s1    = $slow ? mt_rand(3, 12) : mt_rand(1, 6);
                $s2    = $slow ? mt_rand(3, 11) : mt_rand(1, 5);
                $s3    = $slow ? mt_rand(3, 10) : mt_rand(1, 5);
                $gate_in = $created + $wait * 60;
                $times = array(
                    'gate_in_at'      => $gate_in,
                    'survey_start_at' => $gate_in + $s1 * 60,
                    'yard_scan_at'    => $gate_in + ($s1 + $s2) * 60,
                    'gate_out_at'     => $gate_in + ($s1 + $s2 + $s3) * 60
                );
                if ($times['gate_out_at'] > time()) {
                    continue;
                }
                $this->db->insert('queue_transactions', $this->_truck($created, 'COMPLETED', $times));
            }
        }
    }

    // Truk aktif saat demo dimulai, tersebar di semua tahap
    private function _seed_live() {
        $now = time();
        $plan = array(
            array('WAITING_GATE', 4, array()),
            array('WAITING_GATE', 2, array()),
            array('WAITING_GATE', 1, array()),
            array('IN_YARD', 6, array('gate_in_at' => 2)),
            array('IN_YARD', 9, array('gate_in_at' => 4)),
            array('IN_SURVEY', 10, array('gate_in_at' => 6, 'survey_start_at' => 3)),
            array('IN_SURVEY', 12, array('gate_in_at' => 9, 'survey_start_at' => 1)),
            array('AT_YARD', 14, array('gate_in_at' => 10, 'survey_start_at' => 7, 'yard_scan_at' => 2)),
            array('AT_YARD', 16, array('gate_in_at' => 12, 'survey_start_at' => 9, 'yard_scan_at' => 4))
        );
        foreach ($plan as $p) {
            $times = array();
            foreach ($p[2] as $col => $minutes_ago) {
                $times[$col] = $now - $minutes_ago * 60;
            }
            $this->db->insert('queue_transactions', $this->_truck($now - $p[1] * 60, $p[0], $times));
        }
    }
}
