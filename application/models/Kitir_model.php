<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Data kitir & container dari database depo (new_sby). Hanya SELECT, tidak pernah menulis ke new_sby.
 *
 * Barcode kitir = huruf aktivitas + nomor:
 *   Z = Repo In  (Z02155544 -> coins_container_process.CPID CP003D102155544)
 *   A = Import   (A02155830 -> coins_container_process.CPID CP003D102155830)
 *   D = Export   (D1578461  -> book_detail_cpid.bid 1578461 / bnum MT003D101578461)
 *   E = Repo Out (E1577775  -> book_detail_cpid.bid 1577775 / bnum MT003D101577775)
 * Kitir muat (D/E) diambil dari book -> book_detail -> book_detail_cpid.
 */
class Kitir_model extends CI_Model {

    // Awalan CPID kitir bongkar (Z/A); tambahkan jika ada depo lain (misal 'CP003D2')
    private $cpid_prefixes = array('CP003D1');

    // Awalan nomor kitir muat (book_detail_cpid.bnum)
    private $bnum_prefixes = array('MT003D1');

    // Panjang angka setelah awalan (barcode muat dicetak tanpa nol di depan: D1578461)
    const CPID_DIGITS = 8;

    // Status container yang boleh dimuat (tblcontainer CRLASTACT & CRLASTCOND)
    //   Export  : COMPLETED (CO) + kondisi AC
    //   Repo Out: COMPLETED (CO) / WAITING APPROVE (WA) + kondisi AC / DN
    private $load_rules = array(
        'D' => array('act' => array('CO'), 'cond' => array('AC')),
        'E' => array('act' => array('CO', 'WA'), 'cond' => array('AC', 'DN'))
    );

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
        return $this->_is_muat($parsed['activity'])
            ? $this->_find_muat($parsed['activity'], $parsed['number'])
            : $this->_find_bongkar($parsed['activity'], $parsed['number']);
    }

    private function _numbers($number) {
        return array_unique(array($number, str_pad($number, self::CPID_DIGITS, '0', STR_PAD_LEFT)));
    }

    // Kitir Repo In / Import dari coins_container_process
    private function _find_bongkar($activity, $number) {
        $cpids = array();
        foreach ($this->cpid_prefixes as $prefix) {
            foreach ($this->_numbers($number) as $n) {
                $cpids[] = $prefix . $n;
            }
        }

        $row = $this->_db()->query("
            SELECT p.id, p.CPID, p.CRNO, p.CPOPR, p.CPIDELIVER, p.CPINOPOL, p.CPIORDERNO
            FROM coins_container_process p
            WHERE p.CPID IN ?
            ORDER BY p.id DESC
            LIMIT 1
        ", array($cpids))->row();
        if (!$row) {
            return NULL;
        }

        $container = $this->find_container($row->CRNO);
        return (object) array(
            'cp_id'        => $row->id,
            'cpid'         => $row->CPID,
            'activity'     => $activity,
            'barcode'      => $activity . $number,
            'order_no'     => $row->CPIORDERNO,
            'container_no' => strtoupper(trim((string) $row->CRNO)),
            'principal'    => $row->CPOPR,
            'size'         => $container ? $container->size : '',
            'iso_code'     => $container ? $container->iso_code : '',
            'customer'     => $row->CPIDELIVER,
            'nopol'        => $row->CPINOPOL
        );
    }

    // Kitir Export / Repo Out dari book -> book_detail -> book_detail_cpid
    private function _find_muat($activity, $number) {
        $select = "SELECT d.bid, d.bnum, d.cpid, d.crno, d.bdcrctry, d.nopol, d.bdseal,
                          b.bookid, b.brefout, b.bopr, b.bcust, b.breceiv, b.bves, b.bvoy, bd.bcrctry
                   FROM book_detail_cpid d
                   JOIN book_detail bd ON bd.book_detailid = d.book_detailid
                   JOIN book b ON b.bookid = bd.bookid";
        $row = $this->_db()->query("{$select} WHERE d.bid = ? LIMIT 1", array((int) $number))->row();
        if (!$row) {
            $bnums = array();
            foreach ($this->bnum_prefixes as $prefix) {
                foreach ($this->_numbers($number) as $n) {
                    $bnums[] = $prefix . $n;
                }
            }
            $row = $this->_db()->query("{$select} WHERE d.bnum IN ? LIMIT 1", array($bnums))->row();
        }
        if (!$row) {
            return NULL;
        }

        // Repo Out: container sudah diplot di book_detail_cpid.crno; Export: dipilih Yardman
        $crno      = strtoupper(trim((string) $row->crno));
        $iso       = $this->_first_filled(array($row->bdcrctry, $row->bcrctry));
        $container = $crno !== '' ? $this->find_container($crno) : NULL;

        return (object) array(
            'cp_id'        => $row->bid,
            'cpid'         => $row->bnum,
            'activity'     => $activity,
            'barcode'      => $activity . $number,
            'order_no'     => $row->brefout,
            'container_no' => $crno,
            'principal'    => $this->_first_filled(array($row->bopr, $row->bcust)),
            'size'         => $container ? $container->size : $this->size_of($iso),
            'iso_code'     => $iso,
            'customer'     => $row->breceiv,
            'nopol'        => $row->nopol
        );
    }

    // Size dari kode ISO container ("22G1" -> "GP 20 / 8.60"), kosong jika tidak dikenal
    public function size_of($iso_code) {
        if (trim((string) $iso_code) === '') {
            return '';
        }
        $row = $this->_db()->query("SELECT CTCODE, CCLENGTH, CCHEIGHT FROM tblcontainer_code WHERE CCCODE = ? LIMIT 1", array($iso_code))->row();
        return $row ? $this->_format_size($row->CTCODE, $row->CCLENGTH, $row->CCHEIGHT) : $iso_code;
    }

    private function _format_size($type, $length, $height) {
        // Format size sama seperti di kitir: "HC 40 / 9.60"
        $size = trim($type . ' ' . ($length !== NULL ? (int) $length : ''));
        if ($height !== NULL) {
            $size .= ' / ' . number_format((float) $height, 2, '.', '');
        }
        return $size;
    }

    // Data container di depo dari tblcontainer, NULL jika nomor container tidak terdaftar
    public function find_container($crno) {
        $crno = strtoupper(trim((string) $crno));
        if ($crno === '') {
            return NULL;
        }
        $row = $this->_db()->query("
            SELECT t.CRNO, t.CCCODE, t.CROWNER, t.CRLASTACT, t.CRLASTCOND, t.CRCPID,
                   COALESCE(c.CTCODE, t.MTCODE) AS CTCODE, c.CCLENGTH, c.CCHEIGHT, p.CPOPR
            FROM tblcontainer t
            LEFT JOIN tblcontainer_code c ON c.CCCODE = t.CCCODE
            LEFT JOIN coins_container_process p ON p.CPID = t.CRCPID
            WHERE t.CRNO = ?
            LIMIT 1
        ", array($crno))->row();

        if (!$row) {
            return NULL;
        }

        return (object) array(
            'container_no' => $row->CRNO,
            'iso_code'     => $row->CCCODE,
            'size'         => $this->_format_size($row->CTCODE, $row->CCLENGTH, $row->CCHEIGHT),
            'principal'    => $this->_first_filled(array($row->CPOPR, $row->CROWNER)),
            'last_act'     => strtoupper(trim((string) $row->CRLASTACT)),
            'last_cond'    => strtoupper(trim((string) $row->CRLASTCOND))
        );
    }

    // Container boleh dimuat untuk aktivitas ini? NULL jika boleh, selain itu teks alasannya
    public function load_problem($container, $activity) {
        $rule = $this->load_rules[$activity];
        if (in_array($container->last_act, $rule['act'], TRUE) && in_array($container->last_cond, $rule['cond'], TRUE)) {
            return NULL;
        }
        return 'status ' . ($container->last_act !== '' ? $container->last_act : '-') . ' / kondisi ' . ($container->last_cond !== '' ? $container->last_cond : '-')
            . ' (' . ($activity === 'D' ? 'Export' : 'Repo Out') . ' wajib status ' . implode('/', $rule['act']) . ' dan kondisi ' . implode('/', $rule['cond']) . ')';
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
