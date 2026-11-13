<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Resultados de búsqueda<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div><h1>Resultados de búsqueda</h1><p><?= $termino !== '' ? 'Coincidencias para “' . esc($termino) . '”.' : 'Ingresa un término en el buscador global.' ?></p></div>
</div>
<?php if ($termino !== '' && $cotizaciones === [] && $servicios === [] && $contratos === [] && $usuarios === []): ?>
    <section class="panel empty-state">No se encontraron coincidencias.</section>
<?php endif; ?>
<?php if ($cotizaciones !== []): ?>
    <section class="panel">
        <h2>Cotizaciones</h2>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Institución</th><th>Encargado</th><th>Recibida</th><th></th></tr></thead>
            <tbody><?php foreach ($cotizaciones as $cotizacion): ?><tr>
                <td><?= esc($cotizacion['nombre_colegio']) ?></td><td><?= esc($cotizacion['persona_encargada']) ?></td>
                <td><?= esc($cotizacion['created_at'] ?? '') ?></td><td><a class="button secondary compact" href="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/ver') ?>">Abrir</a></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    </section>
<?php endif; ?>
<?php if ($contratos !== []): ?>
    <section class="panel">
        <h2>Contratos</h2>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Número</th><th>Institución</th><th></th></tr></thead>
            <tbody><?php foreach ($contratos as $contrato): ?><tr>
                <td><?= esc($contrato['numero_contrato'] ?: '#' . $contrato['id']) ?></td><td><?= esc($contrato['nombre_colegio'] ?? '') ?></td>
                <td><a class="button secondary compact" href="<?= base_url('admin/contratos/' . $contrato['id']) ?>">Abrir</a></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    </section>
<?php endif; ?>
<?php if ($servicios !== []): ?>
    <section class="panel">
        <h2>Servicios</h2>
        <ul><?php foreach ($servicios as $servicio): ?><li><?= esc($servicio['nombre_item']) ?> — S/ <?= number_format((float) $servicio['precio_interno'], 2) ?></li><?php endforeach; ?></ul>
    </section>
<?php endif; ?>
<?php if ($usuarios !== []): ?>
    <section class="panel">
        <h2>Usuarios</h2>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Nombre</th><th>Usuario</th><th>Cargo</th><th>Rol</th><th>Estado</th></tr></thead>
            <tbody><?php foreach ($usuarios as $usuario): ?><tr>
                <td><?= esc($usuario['nombre'] ?? '') ?></td><td><?= esc($usuario['usuario'] ?? '') ?></td>
                <td><?= esc($usuario['cargo'] ?? '') ?></td><td><?= esc($usuario['rol'] ?? '') ?></td>
                <td><?= (int) ($usuario['activo'] ?? 0) === 1 ? 'Activo' : 'Inactivo' ?></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    </section>
<?php endif; ?>
<?= $this->endSection() ?>
