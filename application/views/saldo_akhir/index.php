<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-sm-12 col-md-9">
        <h4 class="mb-0"><i class="fa fa-cubes"></i> Saldo Akhir</h4>
    </div>
    <div class="col-sm-12 col-md-3">
        <a href="<?= site_url('tambah_saldo_akhir'); ?>" class="btn btn-success btn-sm btn-block">Tambah Saldo Akhir</a>
    </div>
</div>


<hr class="mt-0" />
<?php
//tampilkan pesan success
if ($this->session->flashdata('success')) {
    echo '<div class="alert alert-success" role="alert">
    ' . $this->session->flashdata('success') . '
  </div>';
}

//tampilkan pesan error
if ($this->session->flashdata('error')) {
    echo '<div class="alert alert-danger" role="alert">
    ' . $this->session->flashdata('error') . '
  </div>';
}
?>
<div class="table-responsive">
    <table class="table table-sm table-hover table-striped" id="tables">
        <thead class="thead-dark">
            <tr>
                <th scope="col">#</th>
                <th scope="col">Kode Barang</th>
                <th scope="col">Nama Barang</th>
                <th scope="col">Tgl Saldo Akhir</th>
                <th scope="col">Saldo Akhir</th>
                <th scope="col">Opsi</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>