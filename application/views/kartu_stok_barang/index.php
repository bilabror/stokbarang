<?php defined('BASEPATH') or exit('No direct script access allowed');



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
    //$this->db->select('kode_barang,detail.harga as harga_beli,tgl_pembelian as date,SUM(qty) as unit_beli, (SUM(qty) * detail.harga) as nilai_beli');
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
    //$this->db->select('kode_barang,detail.harga as harga_jual,tgl_penjualan as date,SUM(qty) as unit_jual, (SUM(qty) * detail.harga) as nilai_jual');
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
            <a href="<?= site_url('kartu_stok_barang'); ?>" class="mb-2 btn btn-sm btn-secondary">
                <i class="fa fa-refresh"></i>
            </a>
        </form>
    </div>
    <div class="col-md-2 col-sm-12 mb-2">
        <a href="<?=site_url("kartu_stok_barang/report?tahun={$yearFilter}&kode_barang={$kodeBarangFilter}") ?>" class="btn btn-danger btn-block btn-sm" target="_blank">
            <i class="fa fa-print"></i> Cetak
        </a>
    </div>
    <div class="col-md-2 col-sm-12 mb-2">
        <a href="<?=site_url("kartu_stok_barang/export?tahun={$yearFilter}&kode_barang={$kodeBarangFilter}") ?>" class="btn btn-success btn-block btn-sm" target="_blank">
            <i class="fa fa-print"></i> Export
        </a>
    </div>
</div>
<table class="table table-sm table-bordered table-striped mt-3">
    <thead class="thead-dark">
        <tr>
            <th scope="col">#</th>
            <th scope="col">Tanggal</th>
            <th scope="col">ID Invoice</th>
            <th scope="col">Stok awal</th>
            <th scope="col" class="text-center">IN</th>
            <th scope="col" class="text-center">OUT</th>
            <!-- <th scope="col">Cash In</th>
                                                <th scope="col">Cash Out</th>-->
            <th scope="col">Stok Akhir</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($isThereData): ?>
        <?php $i = 1; foreach ($finalData as $value): ?>
        <tr>
            <td><?=$i++ ?></td>
            <td><?=$value['date'] ?></td>
            <td><?=$value['id_invoice'] ?></td>
            <td><?=$value['stok_awal'] ?></td>
            <td><?=$value['in'] ?></td>
            <td><?=$value['out'] ?></td>
            <!--<td><?=$value['cash_in'] ?></td>
                                                <td><?=$value['cash_out'] ?></td>-->
            <td><?=$value['balance'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php else : ?>
        <tr>
            <td colspan="7"><center>Data Tidak ditemukan</center></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>