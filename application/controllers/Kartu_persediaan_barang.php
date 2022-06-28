<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kartu_persediaan_barang extends CI_Controller
{
    function __construct() {
        parent::__construct();
        //load library
        $this->load->library(['template', 'form_validation']);
        //load model

        // header('Last-Modified:' . gmdate('D, d M Y H:i:s') . 'GMT');
        //header('Cache-Control: no-cache, must-revalidate, max-age=0');
        //header('Cache-Control: post-check=0, pre-check=0', false);
        //header('Pragma: no-cache');
    }

    public function index() {

        //cek login
        $this->is_login();

        $data = [
            'title' => 'Kartu Persediaan Barang'
        ];

        $this->template->kasir('kartu_persediaan_barang/index', $data);
    }


    private function is_login() {
        if (!$this->session->userdata('UserID')) {
            redirect('dashboard');
        }
    }
}