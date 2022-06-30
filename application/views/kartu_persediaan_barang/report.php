<?php

if (!isset($_GET['kode_barang']) || !isset($_GET['tahun'])) {
    $url = site_url('kartu_stok_barang');
    header("location: {$url}");
    exit();
}
// variables declaratio
$yearFilter = $_GET['tahun'];
$kodeBarangFilter = $_GET['kode_barang'];
$finalData = [];
$barang = $this->db->get('tbl_barang')->result();
$thisBarang = $this->db->get_where('tbl_barang', ['kode_barang' => $kodeBarangFilter])->row();
$isThereData = false;

if (isset($_GET['kode_barang']) && isset($_GET['tahun'])) {

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
<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <style>
        img.kiri {
            float: left;
            margin-right: 20px;
            width: 85px;
        }

        p.ex1 {
            margin-bottom: 75px;
        }

        h4.a,h2.a,h5.a {
            font-family: Tahoma, sans-serif;
            font-weight: bold;
            text-align: center;
            margin: 0;
            padding: 0;
        }


        table.table-pinjam > tbody > tr > td {
            padding: 0 !important;
            margin: 0 !important;
        }

    </style>

    <title></title>
</head>

<body>
    <div class="container">
        <div class="row pt-4">
            <div class="col-md-1">
                <img src="<?= base_url() ?>assets/img/logo.jpeg" class="kiri" alt="">
            </div>
            <div class="col-md-11">
                <h2 class="a">LAPORAN KARTU PRRSEDIAAN BARANG</h2>
                <h4 class="a">KOPERASI KONI SALATIGA</h4>
                <p class="alamat">
                    <center>Jl. Veteran No.41 Kota Salatiga</center>
                </p>
            </div>
        </div>
        <hr style="border: 1px solid black;">


        <div class="row">
            <div class="col">
                <strong>Nama Barang : </strong> <?=$thisBarang->nama_barang ?>  <br>
                <strong>Periode : </strong> <?=$yearFilter ?>
            </div>
        </div>
        <div class="row pt-3">
            <div class="col">
                <table class="table table-bordered table-sm">
                    <thead>
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
                            <td><?= $value['harga_beli'] != "" ? rupiah($value['harga_beli']) : "" ?></td>
                            <td><?= $value['nilai_beli'] != "" ? rupiah($value['nilai_beli']) : "" ?></td>
                            <td><?=$value['unit_jual'] ?></td>
                            <td><?= $value['harga_jual'] != "" ? rupiah($value['harga_jual']) : "" ?></td>
                            <td><?= $value['nilai_jual'] != "" ? rupiah($value['nilai_jual']) : "" ?></td>
                            <td><?=$value['unit_saldo'] ?></td>
                            <td><?= $value['harga_saldo'] != ""? rupiah($value['harga_saldo']) : "" ?></td>
                            <td><?= $value['nilai_saldo'] != ""? rupiah($value['nilai_saldo']) : "" ?></td>
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
        </div>
        <div class="row pt-4">
            <div class="col-md-6">
                <p class="text-white">
                    .
                </p>
                <p class="ex1">
                    Penanggung jawab Koperasi
                </p>
                <div class="d-inline-block" style="width:200px;border-bottom:1px solid black;"></div>
            </br>
            <b>Manager Koperasi</b></br>
    </div>
    <div class="col-md-6 text-right">
        <p>
            Salatiga, <?= tgl_indo(date('Y-m-d')) ?>
        </p>
        <p class="ex1">
            Mengetahui
        </p>
        <div class="d-inline-block" style="width:200px;border-bottom:1px solid black;"></div>
    </br>
    <b>Ketua Koni Kota Salatiga</b></br>
</div>
</div>



</div>

<script>
window.print();
//   window.onafterprint = window.close;
</script>


<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
</body>
</html>