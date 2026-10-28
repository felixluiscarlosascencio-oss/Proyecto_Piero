<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Solicitudes de cotización<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <h1>Solicitudes de cotización</h1>
        <p>Revisa las solicitudes guardadas desde el cotizador público.</p>
    </div>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?>
    <div class="alert success"><?= esc($mensaje) ?></div>
<?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?>
    <div class="alert error"><?= esc($error) ?></div>
<?php endif; ?>

<?php if ($cotizaciones === []): ?>
    <section class="panel empty-state">Todavía no hay solicitudes de cotización.</section>
<?php else: ?>
    <div class="quote-list">
        <?php foreach ($cotizaciones as $cotizacion): ?>
            <?php $servicios = json_decode($cotizacion['servicios_json'], true) ?: []; ?>
            <?php $estadoVisible = $cotizacion['estado'] === 'aprobada_admin' ? 'Aprobada por el Admin' : ucfirst($cotizacion['estado']); ?>
            <article class="panel quote-card">
                <div class="quote-heading">
                    <div>
                        <span class="quote-id">Solicitud #<?= esc($cotizacion['id']) ?></span>
                        <h2><?= esc($cotizacion['nombre_colegio']) ?></h2>
                    </div>
                    <span class="status status-<?= esc($cotizacion['estado']) ?>"><?= esc($estadoVisible) ?></span>
                </div>
                <p><strong>Estudiantes:</strong> <?= esc($cotizacion['cantidad_estudiantes']) ?></p>
                <p><strong>Servicios:</strong> <?= esc(implode(', ', array_column($servicios, 'nombre'))) ?></p>
                <p><strong>Total estimado:</strong> S/ <?= number_format((float) $cotizacion['total_seccion'], 2) ?></p>
                <?php if ($cotizacion['vence_en']): ?>
                    <p><strong>Vigente hasta:</strong> <?= esc(date('d/m/Y', strtotime($cotizacion['vence_en']))) ?></p>
                <?php endif; ?>
                <a class="button secondary" href="<?= base_url('admin/cotizaciones/' . $cotizacion['id']) ?>">Ver y gestionar</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
