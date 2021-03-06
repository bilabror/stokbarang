<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_saldo_akhir extends CI_Model
{

    var $table = 'vsaldoakhir';
    // tbl_saldoakhir
    var $column_order = array(null, 'kode_barang', 'tgl_saldoakhir', 'saldoakhir'); //set column field database untuk datatable order
    var $column_search = array('kode_barang', 'tgl_saldoakhir', 'saldoakhir'); //set column field database untuk datatable search
    var $order = array('kode_barang' => 'asc'); // default order

    function __construct() {
        parent::__construct();
    }

    function getAllData($table = null) {
        return $this->db->get($this->table);
    }

    function getData($table = null, $where = null) {
        $this->db->from($this->table);
        $this->db->where($where);

        return $this->db->get();
    }

    function save($data = null) {
        return $this->db->insert('tbl_saldoakhir', $data);
        return $this->db->insert_id();
    }


    function delete($where = null) {
        $this->db->where($where);
        $this->db->delete('tbl_saldoakhir');

        return $this->db->affected_rows();
    }

    private function _get_datatables_query() {

        $this->db->from($this->table);

        $i = 0;

        foreach ($this->column_search as $item) // loop column
        {
            if ($_POST['search']['value']) // Jika datatable mengirim POST untuk search
            {

                if ($i === 0) // first loop
                {
                    $this->db->group_start(); // open bracket.

                    $this->db->like($item, $_POST['search']['value']);
                } else {
                    $this->db->or_like($item, $_POST['search']['value']);
                }

                if (count($this->column_search) - 1 == $i) {
                    //last loop
                    $this->db->group_end(); //close bracket
                }
            }
            $i++;
        }

        if (isset($_POST['order'])) // Proses order
        {

            $this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        } else if (isset($this->order)) {

            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    function get_datatables() {
        $this->_get_datatables_query();

        if ($_POST['length'] != -1) {
            $this->db->limit($_POST['length'], $_POST['start']);
            $query = $this->db->get();

            return $query->result();
        }
    }

    function count_filtered() {
        $this->_get_datatables_query();
        $query = $this->db->get();

        return $query->num_rows();
    }

    function count_all() {
        $this->db->from($this->table);

        return $this->db->count_all_results();
    }
}