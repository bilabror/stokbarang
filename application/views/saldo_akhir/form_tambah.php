<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-sm-12 col-md-10">
        <h4 class="mb-0"><i class="fa fa-cubes"></i><?=$title; ?></h4>
    </div>
</div>
<hr class="mt-0" />
<?= form_open(); ?>
<div class="col-md-8">
    <div class="form-group row">
        <label for="tanggal" class="col-sm-3 col-form-label">Tanggal Saldo Akhir</label>
        <div class="col-sm-3">
            <input type="text" class="form-control form-control-sm<?= (form_error('tanggal')) ? 'is-invalid' : ''; ?>" name="tanggal" id="date-picker" value="<?= (set_value('tanggal')) ? set_value('tanggal') : date('d/m/Y'); ?>">
            <div class="invalid-feedback">
                <?= form_error('tanggal', '<p class="error-message">', '</p>'); ?>
            </div>
        </div>
    </div>
    <div class="form-group row">
        <label for="nama_barang" class="col-sm-3 col-form-label">Nama Barang</label>
        <div class="col-sm-9">
            <select name="nama_barang" id="nama_barang" class="form-control form-control-sm<?= (form_error('nama_barang')) ? 'is-invalid' : ''; ?>">
                <option value="" selected hidden>Pilih Barang</option>
                <?php foreach ($barang as $value): ?>
                <option value="<?=$value->kode_barang ?>"><?=$value->kode_barang . " - " . $value->nama_barang ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">
                <?= form_error('nama_barang', '<p class="error-message">', '</p>'); ?>
            </div>
        </div>
    </div>

    <div class="form-group row">
        <label for="saldo_akhir" class="col-sm-3 col-form-label">Saldo Akhir</label>
        <div class="col-sm-6">
            <input type="text" class="form-control form-control-sm<?= (form_error('saldo_akhir')) ? 'is-invalid' : ''; ?>" id="saldo_akhir" name="saldo_akhir" placeholder="Saldo Akhir" value="<?= set_value('saldo_akhir'); ?>">
            <div class="invalid-feedback">
                <?= form_error('saldo_akhir', '<p class="error-message">', '</p>'); ?>
            </div>
        </div>
    </div>

    <div class="form-group row">
        <label for="harga_persediaan" class="col-sm-3 col-form-label">Harga Persediaan</label>
        <div class="col-sm-6">
            <input type="text" class="form-control form-control-sm uang<?= (form_error('harga_persediaan')) ? 'is-invalid' : ''; ?>" id="harga_persediaan" name="harga_persediaan" placeholder="Harga Persediaan" value="<?= set_value('harga_persediaan'); ?>">
            <div class="invalid-feedback">
                <?= form_error('harga_persediaan', '<p class="error-message">', '</p>'); ?>
            </div>
        </div>
    </div>

    <div class="form-group row">
        <div class="col-sm-9 offset-md-3">
            <button type="submit" name="submit" value="submit" class="btn btn-primary btn-sm">Tambah Data</button>
            <button type="button" class="btn btn-light btn-sm" onclick="window.history.back()">Kembali</button>
        </div>
    </div>
</div>
<?= form_close(); ?>