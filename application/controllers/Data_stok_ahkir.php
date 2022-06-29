<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Data_stok_ahkir extends CI_Controller
{
    function __construct() {
        parent::__construct();
        //load library
        $this->load->library(['template', 'form_validation']);
        //load model
        $this->load->model('m_stok_ahkir');

        header('Last-Modified:' . gmdate('D, d M Y H:i:s') . 'GMT');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    public function index() {
        //cek apakah user yang login adalah admin atau bukan
        //jika bukan maka alihkan ke dashboard
        $this->is_admin();

        $data = [
            'title' => 'Data Stok Ahkir'
        ];

        $this->template->kasir('data_stok_ahkir/index', $data);
    }

    public function stok() {
        //cek pegawai
        if (!$this->session->userdata('level') || $this->session->userdata('level') != 'pegawai') {
            redirect('dashboard');
        }

        $data = [
            'title' => 'Data Stok Barang'
        ];

        $this->template->kasir('data_barang/stok', $data);
    }

    public function ajax_barang() {
        $this->is_admin();
        //cek apakah request berupa ajax atau bukan, jika bukan maka redirect ke home
        if ($this->input->is_ajax_request()) {
            //ambil list data
            $list = $this->m_barang->get_datatables();
            //siapkan variabel array
            $data = array();
            $no = $_POST['start'];

            foreach ($list as $i) {

                $no++;
                $row = array();
                $row[] = $no;
                $row[] = $i->kode_barang;
                $row[] = $i->nama_barang;
                $row[] = $i->brand;
                $row[] = $i->stok;
                $row[] = '<span class="float-left">Rp.</span><span class="float-right">' . number_format($i->harga, 0, ',', '.') . ',-</span>';
                $row[] = ($i->active == 'Y') ? 'Aktif' : 'Tidak Aktif';
                $row[] = '<a href="' . site_url('edit_barang/' . $i->kode_barang) . '" class="btn btn-warning btn-sm text-white">Edit</a>';

                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->m_barang->count_all(),
                "recordsFiltered" => $this->m_barang->count_filtered(),
                "data" => $data
            );
            //output to json format
            echo json_encode($output);
        } else {
            redirect('dashboard');
        }
    }

    public function ajax_stok_barang() {
        //cek pegawai
        if (!$this->session->userdata('level') || $this->session->userdata('level') != 'pegawai') {
            redirect('dashboard');
        }
        //cek apakah request berupa ajax atau bukan, jika bukan maka redirect ke home
        if ($this->input->is_ajax_request()) {
            //ambil list data
            $list = $this->m_barang->get_datatables();
            //siapkan variabel array
            $data = array();
            $no = $_POST['start'];

            foreach ($list as $i) {

                $no++;
                $row = array();
                $row[] = $no;
                $row[] = $i->kode_barang;
                $row[] = $i->nama_barang;
                $row[] = $i->stok;
                $row[] = '<span class="float-left">Rp.</span><span class="float-right">' . number_format($i->harga, 0, ',', '.') . ',-</span>';

                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->m_barang->count_all(),
                "recordsFiltered" => $this->m_barang->count_filtered(),
                "data" => $data
            );
            //output to json format
            echo json_encode($output);
        } else {
            redirect('dashboard');
        }
    }

    private function is_admin() {
        if (!$this->session->userdata('level') || $this->session->userdata('level') != 'admin') {
            redirect('dashboard');
        }
    }
}