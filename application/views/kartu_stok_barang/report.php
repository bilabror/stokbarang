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

        p.alamat {
            margin: 0;
            padding: 0;
            text-align: center;
        }

        h4.a {
            font-family: Tahoma, sans-serif;
            font-weight: bold;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        h2.a {
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
                <h2 class="a">LAPORAN STOK BARANG</h2>
                <h4 class="a">KOPERASI KONI SALATIGA</h4>
                <p class="alamat">
                    Jl. Veteran No.41 Kota Salatiga
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
                            <th scope="col">#</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">ID Invoice</th>
                            <th scope="col">Stok awal</th>
                            <th scope="col" class="text-center">IN</th>
                            <th scope="col" class="text-center">OUT</th>
                            <th scope="col">Cash In</th>
                            <th scope="col">Cash Out</th>
                            <th scope="col">Balance</th>
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
                            <td><?=$value['cash_in'] ?></td>
                            <td><?=$value['cash_out'] ?></td>
                            <td><?=$value['balance'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else : ?>
                        <tr>
                            <td colspan="9"><center>Data Tidak ditemukan</center></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row pt-4">
            <div class="col-md-6">

            </div>
            <div class="col-md-6 text-right">
                <p>
                    Salatiga, <?= tgl_indo(date('Y-m-d')) ?>
                </p>
                <p class="ex1">
                    Penanggung jawab KOPERASI
                </p>
                Nama Penanggung Jawab</br>
            NIP. 1970051519954401</br>
    </div>
</div>



</div>

<script>
window.print();
window.onafterprint = window.close;
</script>


<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
</body>
</html>