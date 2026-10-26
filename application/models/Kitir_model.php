<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Data kitir & container dari database depo (new_sby). Hanya SELECT, tidak pernah menulis ke new_sby.
 *
 * Barcode kitir = huruf aktivitas + potongan CPID setelah awalan depo:
 *   Z = Repo In  (Z02155544 -> CP003D102155544)
 *   A = Import   (A02155830 -> CP003D102155830)
 *   D = Export   (D1578461  -> MT003D101578461)
 *   E = Repo Out (E1577775  -> MT003D101577775)
 */
class Kitir_model extends CI_Model {

    // Awalan CPID per aktivitas; tambahkan jika ada depo lain (misal 'CP003D2')
    private $cpid_prefixes = array(
        'Z' => array('CP003D1'),
        'A' => array('CP003D1'),
        'D' => array('MT003D1', 'CP003D1'),
        'E' => array('MT003D1', 'CP003D1')
    );

    // Panjang angka setelah awalan CPID (barcode muat dicetak tanpa nol di depan: D1578461)
    const CPID_DIGITS = 8;

    // Nilai tblcontainer.CRLASTACT yang berarti container sedang berada di depo
    private $in_depot_acts = array('BI');

    private $depo_db = NULL;

    private function _db() {
        if ($this->depo_db === NULL) {
            $this->depo_db = $this->load->database('new_sby', TRUE);
        }
        return $this->depo_db;
    }

    // Pecah hasil scan barcode kitir ("D1578461" -> D, 1578461), FALSE jika bukan kitir
    public function parse_barcode($code) {
        $code = strtoupper(trim((string) $code));
        if (!preg_match('/^([ZADE])(\d{6,12})$/', $code, $m)) {
            return FALSE;
        }
        return array('activity' => $m[1], 'number' => $m[2]);
    }

    // Cari data kitir berdasarkan barcode, NULL jika tidak ditemukan
    public function find_by_barcode($code) {
        $parsed = $this->parse_barcode($code);
        if ($parsed === FALSE) {
            return NULL;
        }
        $activity = $parsed['activity'];
        $numbers  = array_unique(array($parsed['number'], str_pad($parsed['number'], self::CPID_DIGITS, '0', STR_PAD_LEFT)));

        $cpids = array();
        foreach ($this->cpid_prefixes[$activity] as $prefix) {
            foreach ($numbers as $number) {
                $cpids[] = $prefix . $number;
            }
        }

        $select = "SELECT p.id, p.CPID, p.CRNO, p.CPOPR, p.CPIDELIVER, p.CPINOPOL, p.CPIORDERNO,
                          p.CPORECEIV, p.EMKLOUT, p.CPCUST, p.CPONOPOL, p.CPOORDERNO
                   FROM coins_container_process p";
        $row = $this->_db()->query("{$select} WHERE p.CPID IN ? ORDER BY p.id DESC LIMIT 1", array($cpids))->row();

        // Kitir muat: cadangan jika nomor kitir tersimpan sebagai nomor order keluar
        if (!$row && $this->_is_muat($activity)) {
            $row = $this->_db()->query("{$select} WHERE p.CPOORDERNO IN ? ORDER BY p.id DESC LIMIT 1", array($cpids))->row();
        }
        if (!$row) {
            return NULL;
        }

        $crno      = strtoupper(trim((string) $row->CRNO));
        $container = $crno !== '' ? $this->find_container($crno) : NULL;
        $muat      = $this->_is_muat($activity);

        return (object) array(
            'cp_id'        => $row->id,
            'cpid'         => $row->CPID,
            'activity'     => $activity,
            'barcode'      => $activity . $parsed['number'],
            'order_no'     => $muat ? $row->CPOORDERNO : $row->CPIORDERNO,
            'container_no' => $crno,
            'principal'    => $row->CPOPR,
            'size'         => $container ? $container->size : '',
            'customer'     => $muat ? $this->_first_filled(array($row->CPORECEIV, $row->EMKLOUT, $row->CPCUST)) : $row->CPIDELIVER,
            'nopol'        => $muat ? $row->CPONOPOL : $row->CPINOPOL
        );
    }

    // Data container di depo dari tblcontainer, NULL jika nomor container tidak terdaftar
    public function find_container($crno) {
        $row = $this->_db()->query("
            SELECT t.CRNO, t.CCCODE, t.CROWNER, t.CRLASTACT, t.CRCPID,
                   COALESCE(c.CTCODE, t.MTCODE) AS CTCODE, c.CCLENGTH, c.CCHEIGHT, p.CPOPR
            FROM tblcontainer t
            LEFT JOIN tblcontainer_code c ON c.CCCODE = t.CCCODE
            LEFT JOIN coins_container_process p ON p.CPID = t.CRCPID
            WHERE t.CRNO = ?
            LIMIT 1
        ", array(strtoupper(trim((string) $crno))))->row();

        if (!$row) {
            return NULL;
        }

        // Format size sama seperti di kitir: "HC 40 / 9.60"
        $size = trim($row->CTCODE . ' ' . ($row->CCLENGTH !== NULL ? (int) $row->CCLENGTH : ''));
        if ($row->CCHEIGHT !== NULL) {
            $size .= ' / ' . number_format((float) $row->CCHEIGHT, 2, '.', '');
        }

        return (object) array(
            'container_no' => $row->CRNO,
            'iso_code'     => $row->CCCODE,
            'size'         => $size,
            'principal'    => $this->_first_filled(array($row->CPOPR, $row->CROWNER)),
            'last_act'     => $row->CRLASTACT,
            'in_depot'     => in_array(strtoupper((string) $row->CRLASTACT), $this->in_depot_acts, TRUE)
        );
    }

    // Bandingkan principal tanpa beda spasi/tanda baca ("WAN HAI" = "WANHAI")
    public function same_principal($a, $b) {
        $norm = function ($v) {
            return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $v));
        };
        return $norm($a) !== '' && $norm($a) === $norm($b);
    }

    private function _is_muat($activity) {
        return in_array($activity, array('D', 'E'), TRUE);
    }

    private function _first_filled($values) {
        foreach ($values as $v) {
            if (trim((string) $v) !== '') {
                return trim($v);
            }
        }
        return '';
    }
}
