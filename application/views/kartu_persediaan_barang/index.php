<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php


// variables declaration
$yearFilter = "";
$kodeBarangFilter = "";
$finalData = [];
$barang = $this->db->get('tbl_barang')->result();
$isThereData = false;




if (isset($_GET['kode_barang']) && isset($_GET['tahun'])) {

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

    if (count($finalData) > 1)
        $isThereData = true;

}





?>


<div class="row">
    <div class="col-sm-12 col-md-10">
        <h4 class="mb-0"><i class="fa fa-file-text"></i> <?=$title ?></h4>
    </div>
</div>
<hr class="mt-0" />
<?php
if ($this->session->flashdata('alert')) {
    echo '<div class="alert alert-danger" role="alert">
    ' . $this->session->flashdata('alert') . '
  </div>';
}
?>

<div class="row">
    <div class="col-md-8 col-sm-12">
        <form action="" class="form-inline">
            <div class="form-group mx-sm-2 mb-2">
                <label for="tahun" class="sr-only">Tahun</label>
                <select name="tahun" id="tahun" class="form-control form-control-sm" style="min-width:130px" required>
                    <option value="">Pilih Tahun</option>
                    <?php for ($i = 2010; $i < 2040; $i++) :
                    $selected = ($i == $yearFilter) ? 'selected' : '';
                    ?>
                    <option value="<?=$i ?>" <?=$selected ?>><?=$i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group mx-sm-2 mb-2">
                <label for="kode_barang" class="sr-only">Bulan</label>
                <select name="kode_barang" id="kode_barang" class="form-control form-control-sm" style="min-width:150px" required>
                    <option value="" selected hidden>Pilih Barang</option>
                    <?php foreach ($barang as $val): ?>
                    <option value="<?=$val->kode_barang ?>" <?= $kodeBarangFilter == $val->kode_barang ? 'selected' : '' ?>><?=$val->nama_barang ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mb-2 btn-sm mr-2">
                <i class="fa fa-search"></i>
            </button>
            <a href="<?= site_url('kartu_persediaan_barang'); ?>" class="mb-2 btn btn-sm btn-secondary">
                <i class="fa fa-refresh"></i>
            </a>
        </form>
    </div>
    <div class="col-md-2 col-sm-12 mb-2">
        <a href="" class="btn btn-success btn-block btn-sm" target="_blank">
            <i class="fa fa-print"></i> Cetak
        </a>
    </div>
    <div class="col-md-2 col-sm-12 mb-2">
        <a href="" class="btn btn-success btn-block btn-sm" target="_blank">
            <i class="fa fa-print"></i> Export
        </a>
    </div>
</div>
<div class="table-responsive">

    <table class="table table-sm table-bordered table-striped mt-3" id="table2">
        <thead class="thead-dark">
            <tr>
                <th scope="col" rowspan="2" class="text-center">Tanggal</th>
                <th scope="col" colspan="3" class="text-center">Pembelian</th>
                <th scope="col" colspan="3" class="text-center">Harga Pokok Penjualan</th>
                <th scope="col" colspan="3" class="text-center">Saldo</th>
            </tr>
            <tr>
                <th scope="col">Unit</th>
                <th scope="col">Harga</th>
                <th scope="col">Nilai</th>
                <th scope="col">Unit</th>
                <th scope="col">Harga</th>
                <th scope="col">Nilai</th>
                <th scope="col">Unit</th>
                <th scope="col">Harga</th>
                <th scope="col">Nilai</th>
            </tr>
        </thead>
        <tbody>

            <?php if ($isThereData): ?>

            <?php foreach ($finalData as $value): ?>
            <tr>
                <td><?=$value['date'] ?></td>
                <td><?=$value['unit_beli'] ?></td>
                <td><?=$value['harga_beli'] ?></td>
                <td><?=$value['nilai_beli'] ?></td>
                <td><?=$value['unit_jual'] ?></td>
                <td><?=$value['harga_jual'] ?></td>
                <td><?=$value['nilai_jual'] ?></td>
                <td><?=$value['unit_saldo'] ?></td>
                <td><?=$value['harga_saldo'] ?></td>
                <td><?=$value['nilai_saldo'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php else : ?>
            <tr>
                <td colspan="10"><center>Data Tidak ditemukan</center></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>