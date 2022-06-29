<?php
defined('BASEPATH') or exit('No direct script access allowed');



require('./application/third_party/phpoffice/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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


    public function report() {
        $this->load->view('kartu_persediaan_barang/report');
    }



    public function export_data() {

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
        $this->db->select('kode_barang,detail.harga as harga_beli,tgl_pembelian as date,SUM(qty) as unit_beli, (SUM(qty) * detail.harga) as nilai_beli');
        $this->db->from('tbl_detail_pembelian detail');
        $this->db->join('tbl_pembelian beli', 'detail.id_pembelian=beli.id_pembelian');
        $this->db->join('tbl_barang brg', 'brg.kode_barang=detail.id_barang');
        $this->db->group_by(array("kode_barang", "tgl_pembelian"));
        $this->db->where('kode_barang', $kodeBarangFilter);
        $this->db->where('year(tgl_pembelian)', $yearFilter);
        $this->db->order_by('tgl_pembelian', 'asc');
        $pembelian = $this->db->get()->result();

        // query penjualan
        $this->db->select('kode_barang,detail.harga as harga_jual,tgl_penjualan as date,SUM(qty) as unit_jual, (SUM(qty) * detail.harga) as nilai_jual');
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


        $finalData[0]["date"] = $saldoakhir->tgl_saldoakhir;
        $finalData[0]["unit_beli"] = "";
        $finalData[0]["harga_beli"] = "";
        $finalData[0]["nilai_beli"] = "";
        $finalData[0]["unit_jual"] = "";
        $finalData[0]["harga_jual"] = "";
        $finalData[0]["nilai_jual"] = "";
        $finalData[0]['unit_saldo'] = $saldoakhir->saldoakhir;
        $finalData[0]["harga_saldo"] = $saldoakhir->harga_persediaan;
        $finalData[0]["nilai_saldo"] = $saldoakhir->nilai_saldoakhir;


        // change key to index key
        $merge2 = [];
        foreach ($merge as $value) $merge2[] = $value;


        $i = 1;
        foreach ($merge2 as $key => $value) {
            $soldNow = $value->unit_jual ?? 0;
            $buyNow = $value->unit_beli ?? 0;
            $buyPriceNow = $value->harga_beli ?? 0;
            $soldPriceNow = isset($value->harga_jual) ? $finalData[$i-1]['harga_saldo'] : 0;
            $unitSaldo = (int)$finalData[$i-1]['unit_saldo'] - $soldNow + $buyNow;
            $hargaSaldo = (int)($finalData[$i-1]['harga_saldo'] + $buyPriceNow + $soldPriceNow) / 2;
            $nilaiJual = isset($value->nilai_jual) ? $value->unit_jual * $soldPriceNow : '';
            $nilaiSaldo = $unitSaldo * $hargaSaldo;

            $finalData[$i]["date"] = date("d M", strtotime($value->date));
            $finalData[$i]["unit_beli"] = $value->unit_beli ?? "";
            $finalData[$i]["harga_beli"] = $value->harga_beli ?? "";
            $finalData[$i]["nilai_beli"] = $value->nilai_beli ?? "";
            $finalData[$i]["unit_jual"] = $value->unit_jual ?? "";
            $finalData[$i]["harga_jual"] = isset($value->harga_jual) ? $finalData[$i-1]['harga_saldo'] : '';
            $finalData[$i]["nilai_jual"] = $nilaiJual;
            $finalData[$i]["unit_saldo"] = $unitSaldo;
            $finalData[$i]["harga_saldo"] = $hargaSaldo;
            $finalData[$i]["nilai_saldo"] = $nilaiSaldo;
            $i++;
        }

        $spreadsheet = new Spreadsheet;

        $spreadsheet->setActiveSheetIndex(0)
        ->setCellValue('A1', 'TANGGAL')
        ->setCellValue('B1', 'PEMBELIAN')
        ->setCellValue('E1', 'HARGA POKOK PENJUALAN')
        ->setCellValue('H1', 'SALDO')
        ->setCellValue('B2', 'UNIT')
        ->setCellValue('C2', 'HARGA')
        ->setCellValue('D2', 'NILAI')
        ->setCellValue('E2', 'UNIT')
        ->setCellValue('F2', 'HARGA')
        ->setCellValue('G2', 'NILAI')
        ->setCellValue('H2', 'UNIT')
        ->setCellValue('I2', 'HARGA')
        ->setCellValue('J2', 'NILAI');


        $kolom = 3;
        $nomor = 1;
        foreach ($finalData as $item) {

            $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A' . $kolom, $item['date'])
            ->setCellValue('B' . $kolom, $item['unit_beli'])
            ->setCellValue('C' . $kolom, $item['harga_beli'])
            ->setCellValue('D' . $kolom, $item['nilai_beli'])
            ->setCellValue('E' . $kolom, $item['unit_jual'])
            ->setCellValue('F' . $kolom, $item['harga_jual'])
            ->setCellValue('G' . $kolom, $item['nilai_jual'])
            ->setCellValue('H' . $kolom, $item['unit_saldo'])
            ->setCellValue('I' . $kolom, $item['harga_saldo'])
            ->setCellValue('J' . $kolom, $item['nilai_saldo']);

            $kolom++;
            $nomor++;

        }

        $spreadsheet->getActiveSheet()->mergeCells('A1:A2');
        $spreadsheet->getActiveSheet()->mergeCells('B1:D1');
        $spreadsheet->getActiveSheet()->mergeCells('E1:G1');
        $spreadsheet->getActiveSheet()->mergeCells('H1:J1');

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