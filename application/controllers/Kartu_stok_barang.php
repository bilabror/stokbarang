<?php
defined('BASEPATH') or exit('No direct script access allowed');


require('./application/third_party/phpoffice/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Kartu_stok_barang extends CI_Controller
{
    function __construct() {
        parent::__construct();
        //load library
        $this->load->library(['template', 'form_validation']);
        //load model

        header('Last-Modified:' . gmdate('D, d M Y H:i:s') . 'GMT');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    public function index() {
        //cek login
        $this->is_login();

        $data = [
            'title' => 'kartu Stok Barang'
        ];

        $this->template->kasir('kartu_stok_barang/index', $data);
    }


    public function report() {
        $this->load->view('kartu_stok_barang/report');
    }


    public function export_data() {

        if (!isset($_GET['kode_barang']) || !isset($_GET['tahun'])) {
            $url = site_url('kartu_stok_barang');
            header("location: {$url}");
            exit();
        }

        if (!isset($_GET['kode_barang']) || !isset($_GET['tahun'])) {
            $url = site_url('kartu_stok_barang');
            header("location: {$url}");
            exit();
        }

        $finalData = [];
        $isThereData = false;
        $yearFilter = $_GET['tahun'];
        $kodeBarangFilter = $_GET['kode_barang'];
        $lastYear = $yearFilter-1;


        // query saldo akhir
        $this->db->select('*,(saldoakhir*harga_persediaan) as nilai_saldoakhir');
        $this->db->from('tbl_saldoakhir');
        $this->db->where('kode_barang', $kodeBarangFilter);
        $this->db->where('year(tgl_saldoakhir)', $lastYear);
        $this->db->order_by('tgl_saldoakhir', 'DESC');
        $saldoakhir = $this->db->get()->row();

        // query pembelian
        $this->db->select('kode_barang,detail.id_pembelian,SUM(qty) as stok_in,(SUM(qty) * detail.harga) as cash_in,tgl_pembelian as date');
        $this->db->from('tbl_detail_pembelian detail');
        $this->db->join('tbl_pembelian beli', 'detail.id_pembelian=beli.id_pembelian');
        $this->db->join('tbl_barang brg', 'brg.kode_barang=detail.id_barang');
        $this->db->group_by(array("kode_barang", "tgl_pembelian"));
        $this->db->where('kode_barang', $kodeBarangFilter);
        $this->db->where('year(tgl_pembelian)', $yearFilter);
        $this->db->order_by('tgl_pembelian', 'asc');
        $pembelian = $this->db->get()->result();


        // query penjualan
        $this->db->select('kode_barang,detail.id_penjualan,SUM(qty) as stok_out,(SUM(qty)*detail.harga) as cash_out,tgl_penjualan as date');
        $this->db->from('tbl_detail_penjualan detail');
        $this->db->join('tbl_penjualan jual', 'detail.id_penjualan=jual.id_penjualan');
        $this->db->join('tbl_barang brg', 'brg.kode_barang=detail.id_barang');
        $this->db->group_by(array("kode_barang", "tgl_penjualan"));
        $this->db->where('kode_barang', $kodeBarangFilter);
        $this->db->where('year(tgl_penjualan)', $yearFilter);
        $this->db->order_by('tgl_penjualan', 'asc');
        $penjualan = $this->db->get()->result();



        // combine data pembelian dan data penjualan
        $merge = [];
        foreach (array_merge($pembelian, $penjualan) as $entry) {
            // if an entry for this user id hasn't been created in the result, add this object
            if (!isset($merge[$entry->date])) {
                $merge[$entry->date] = $entry;
                // otherwise, iterate this object and add the values of its keys to the existing entry
            } else {
                foreach ($entry as $key => $value) {
                    $merge[$entry->date]->$key = $value;
                }
            }
        }

        // sort array
        ksort($merge);


        // change key to index key
        $merge2 = [];
        foreach ($merge as $value) $merge2[] = $value;


        $i = 0;
        $balance = "";

        foreach ($merge2 as $key => $value) {
            if ($i > 0)
                $finalData[$i]["stok_awal"] = $balance;
            $finalData[0]["stok_awal"] = $saldoakhir->saldoakhir;
            $in = $value->stok_in ?? 0;
            $out = $value->stok_out ?? 0;
            $balance = (int)$finalData[$i]["stok_awal"] + $in - $out;

            $finalData[$i]["date"] = date("d M", strtotime($value->date));
            $finalData[$i]["id_invoice"] = $value->id_pembelian ?? $value->id_penjualan;
            $finalData[$i]["in"] = $value->stok_in ?? "";
            $finalData[$i]["out"] = $value->stok_out ?? "";
            $finalData[$i]["cash_in"] = $value->cash_in ?? "";
            $finalData[$i]["cash_out"] = $value->cash_out ?? "";
            $finalData[$i]["balance"] = $balance;
            $i++;
        }

        $spreadsheet = new Spreadsheet;

        $spreadsheet->setActiveSheetIndex(0)
        ->setCellValue('A1', 'NO')
        ->setCellValue('B1', 'TANGGAL')
        ->setCellValue('C1', 'ID INVOICE')
        ->setCellValue('D1', 'STOK AWAL')
        ->setCellValue('E1', 'IN')
        ->setCellValue('F1', 'OUT')
        ->setCellValue('G1', 'CASH IN')
        ->setCellValue('H1', 'CASH OUT')
        ->setCellValue('I1', 'BALANCE');
        //Tanggal	ID Invoice	Stok awal	IN	OUT	Cash In	Cash Out	Balance
        $kolom = 2;
        $nomor = 1;
        foreach ($finalData as $item) {

            $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A' . $kolom, $nomor)
            ->setCellValue('B' . $kolom, $item['date'])
            ->setCellValue('C' . $kolom, $item['id_invoice'])
            ->setCellValue('D' . $kolom, $item['stok_awal'])
            ->setCellValue('E' . $kolom, $item['in'])
            ->setCellValue('F' . $kolom, $item['out'])
            ->setCellValue('G' . $kolom, $item['cash_in'])
            ->setCellValue('H' . $kolom, $item['cash_out'])
            ->setCellValue('I' . $kolom, $item['balance']);

            $kolom++;
            $nomor++;

        }

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Data Aset.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
    }


    private function is_login() {
        if (!$this->session->userdata('UserID')) {
            redirect('dashboard');
        }
    }
}