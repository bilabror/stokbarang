<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_kartu_stok extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    function getKartuStok($tanggal)
    {
        $table = 'tbl_barang b
                    LEFT JOIN
                    (SELECT qty, id_barang FROM tbl_penjualan pn
                    LEFT JOIN tbl_detail_penjualan dpn ON(pn.id_penjualan = dpn.id_penjualan AND tgl_penjualan = \'' . $tanggal . '\')) AS c ON(b.kode_barang = c.id_barang)
                    LEFT JOIN
                    (SELECT qty, id_barang FROM tbl_pembelian pm
                    LEFT JOIN tbl_detail_pembelian dpm ON(pm.id_pembelian = dpm.id_pembelian AND tgl_pembelian = \'' . $tanggal . '\')) AS d ON(b.kode_barang = d.id_barang)
                    LEFT JOIN
                    (SELECT qty, id_barang FROM tbl_penjualan pn
                    LEFT JOIN tbl_detail_penjualan dpn ON(pn.id_penjualan = dpn.id_penjualan AND tgl_penjualan > \'' . $tanggal . '\')) AS e ON(b.kode_barang = e.id_barang)
                    LEFT JOIN
                    (SELECT qty, id_barang FROM tbl_pembelian pm
                    LEFT JOIN tbl_detail_pembelian dpm ON(pm.id_pembelian = dpm.id_pembelian AND tgl_pembelian > \'' . $tanggal . '\')) AS f ON(b.kode_barang = f.id_barang) ';

        $select = 'kode_barang, nama_barang, brand, stok, SUM(c.qty) AS qty_penjualan, SUM(d.qty) AS qty_pembelian, SUM(e.qty) AS qty_penjualan_new, SUM(f.qty) AS qty_pembelian_new';

        $group = ['kode_barang', 'nama_barang', 'brand', 'stok'];

        $this->db->select($select);
        $this->db->from($table);
        $this->db->group_by($group);

        return $this->db->get();
    }