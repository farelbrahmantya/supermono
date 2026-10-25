<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Data kitir dari database depo (new_sby).
 *
 * Barcode kitir = "Z" + potongan CPID setelah awalan depo,
 * contoh: Z02155544 -> CPID CP003D102155544.
 */
class Kitir_model extends CI_Model {

    // Awalan CPID per depo; tambahkan jika ada depo lain (misal 'CP003D2')
    private $cpid_prefixes = array('CP003D1');

    private $depo_db = NULL;

    private function _db() {
        if ($this->depo_db === NULL) {
            $this->depo_db = $this->load->database('new_sby', TRUE);
        }
        return $this->depo_db;
    }

    // Ambil angka dari hasil scan barcode kitir ("Z02155544" -> "02155544"), FALSE jika bukan kitir
    public function parse_barcode($code) {
        $code = strtoupper(trim((string) $code));
        return preg_match('/^Z(\d{6,12})$/', $code, $m) ? $m[1] : FALSE;
    }

    // Cari data container berdasarkan barcode kitir, NULL jika tidak ditemukan
    public function find_by_barcode($code) {
        $number = $this->parse_barcode($code);
        if ($number === FALSE) {
            return NULL;
        }

        $cpids = array();
        foreach ($this->cpid_prefixes as $prefix) {
            $cpids[] = $prefix . $number;
        }

        $row = $this->_db()->query("
            SELECT p.id, p.CPID, p.CRNO, p.CPOPR, p.CPIDELIVER, p.CPINOPOL, p.CPIORDERNO,
                   COALESCE(c.CTCODE, t.MTCODE) AS CTCODE, c.CCLENGTH, c.CCHEIGHT
            FROM coins_container_process p
            LEFT JOIN tblcontainer t ON t.CRNO = p.CRNO
            LEFT JOIN tblcontainer_code c ON c.CCCODE = t.CCCODE
            WHERE p.CPID IN ?
            ORDER BY p.id DESC
            LIMIT 1
        ", array($cpids))->row();

        if (!$row) {
            return NULL;
        }

        // Format size sama seperti di kitir: "HC 40 / 9.60"
        $size = trim($row->CTCODE . ' ' . ($row->CCLENGTH !== NULL ? (int) $row->CCLENGTH : ''));
        if ($row->CCHEIGHT !== NULL) {
            $size .= ' / ' . number_format((float) $row->CCHEIGHT, 2, '.', '');
        }

        return (object) array(
            'cp_id'        => $row->id,
            'cpid'         => $row->CPID,
            'barcode'      => 'Z' . $number,
            'order_no'     => $row->CPIORDERNO,
            'container_no' => $row->CRNO,
            'principal'    => $row->CPOPR,
            'size'         => $size,
            'customer'     => $row->CPIDELIVER,
            'nopol'        => $row->CPINOPOL
        );
    }
}
