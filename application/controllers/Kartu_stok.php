<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kartu_Stok extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        //load library
        $this->load->library(['template', 'form_validation']);
        //load model
        $this->load->model('m_kartu_stok');

        header('Last-Modified:' . gmdate('D, d M Y H:i:s') . 'GMT');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

          }

    public function index()
    {
        redirect('dashboard');
    }

    public function data_kartu_stok()
    {
        //cek login
        $this->is_login();

        if ($this->input->post('cari', TRUE) == 'Search') {
            //validasi input data tanggal
            $this->form_validation->set_rules(
                'tanggal',
                'Tanggal',
                'required|callback_checkDateFormat',
                array(
                    'required' => '{field} wajib diisi',
                    'checkDateFormat' => '{field} tidak valid'
                )
            );

            if ($this->form_validation->run() == TRUE) {
                $tanggal = $this->security->xss_clean($this->input->post('tanggal', TRUE));
            } else {
                $this->session->set_flashdata('alert', validation_errors('<p class="my-0">', '</p>'));

                redirect('kartu_stok');
            }
        } else {
            $tanggal = date('d/m/Y');
        }

        $getData = $this->m_laporan->getDataStokHarian(date('Y-m-d', strtotime(str_replace('/', '-', $tanggal))));

        $data = [
            'title' => 'Laporan Kartu Stok Barang Koperasi KONI Salatiga',
            'tanggal' => $tanggal,
            'data' => $getData
        ];

        $this->template->kasir('laporan/kartu_stok', $data);
    }

    public function cetak_kartu_stok($date)
    {
        $this->is_login();

        if ($this->cekTanggal($date) == false) {
            redirect('kartu_stok');
        }

        $getData = $this->m_laporan->getDataStokHarian($date);

        $data = [
            'title' => 'Laporan Kartu Stok Barang Koperasi Komite Olahraga Nasional Indonesia',
            'tanggal' => $date,
            'data' => $getData
        ];

        $this->template->cetak('cetak/kartu_stok', $data);
    }