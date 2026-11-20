<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Acceso denegado<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <h1>Acceso denegado</h1>
        <p><?= esc($mensaje) ?></p>
    </div>
</div>
<section class="panel empty-state">
    <p>Si necesitas este permiso, pide a un administrador que lo habilite en tu cuenta.</p>
    <p><a class="button secondary" href="<?= base_url('admin/dashboard') ?>">Volver al dashboard</a></p>
</section>
<?= $this->endSection() ?>
