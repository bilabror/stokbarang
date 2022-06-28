<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Saldo_akhir extends CI_Controller
{
    function __construct() {
        parent::__construct();
        //load library
        $this->load->library(['template', 'form_validation']);
        //load model
        $this->load->model('m_saldo_akhir');
        //$this->load->model('barang', 'm_barang');

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
            'title' => 'Saldo Akhir'
        ];

        $this->template->kasir('saldo_akhir/index', $data);
    }


    public function tambah_data() {
        //cek apakah user yang login adalah admin atau bukan
        //jika bukan maka alihkan ke dashboard
        $this->is_admin();


        if ($this->input->post('submit', TRUE) == 'submit') {
            //set rules form validasi


            $this->form_validation->set_rules(
                'tanggal',
                'Tanggal Saldo Akhir',
                'required',
                array(
                    'required' => '{field} wajib diisi',
                )
            );

            $this->form_validation->set_rules(
                'nama_barang',
                'Barang',
                'required',
                array(
                    'required' => '{field} wajib diisi'
                )
            );


            $this->form_validation->set_rules(
                'saldo_akhir',
                'Saldo Akhir',
                "required|regex_match[/^[0-9.]+$/]",
                array(
                    'required' => '{field} wajib diisi',
                    'regex_match' => '{field} hanya boleh angka'
                )
            );

            $this->form_validation->set_rules(
                'harga_persediaan',
                'Harga Persediaan',
                "required|regex_match[/^[0-9.]+$/]",
                array(
                    'required' => '{field} wajib diisi',
                    'regex_match' => '{field} hanya boleh angka'
                )
            );

            //jika data sudah valid maka lakukan proses penyimpanan
            if ($this->form_validation->run() == TRUE) {
                //masukkan data ke variable array
                $tgl = date('Y-m-d', strtotime(str_replace('/', '-', $this->security->xss_clean($this->input->post('tanggal', TRUE)))));
                $simpan = array(
                    'kode_barang' => $this->security->xss_clean($this->input->post('nama_barang', TRUE)),
                    'tgl_saldoakhir' => $tgl,
                    'saldoakhir' => $this->security->xss_clean($this->input->post('saldo_akhir', TRUE)),
                    'harga_persediaan' => str_replace('.', '', $this->security->xss_clean($this->input->post('harga_persediaan', TRUE)))
                );

                //simpan ke database
                $save = $this->m_saldo_akhir->save($simpan);

                if ($save) {
                    $this->session->set_flashdata('success', 'Data Saldo Akhir berhasil ditambah...');

                    redirect('saldo_akhir');
                }
            }
        }

        $data = [
            'title' => 'Tambah Saldo Akhir',
            'barang' => $this->db->get('tbl_barang')->result()
        ];


        $this->template->kasir('saldo_akhir/form_tambah', $data);
    }


    public function ajax_saldo_akhir() {
        $this->is_admin();
        //cek apakah request berupa ajax atau bukan, jika bukan maka redirect ke home
        if ($this->input->is_ajax_request()) {
            //ambil list data
            $list = $this->m_saldo_akhir->get_datatables();
            //siapkan variabel array
            $data = array();
            $no = $_POST['start'];

            foreach ($list as $i) {

                $no++;
                $row = array();
                $row[] = $no;
                $row[] = $i->kode_barang;
                $row[] = $i->nama_barang;
                $row[] = $i->tgl_saldoakhir;
                $row[] = $i->saldoakhir;

                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->m_saldo_akhir->count_all(),
                "recordsFiltered" => $this->m_saldo_akhir->count_filtered(),
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