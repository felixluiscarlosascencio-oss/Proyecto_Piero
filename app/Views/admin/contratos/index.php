<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Contratos<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <h1>Contratos</h1>
        <p>Consulta los contratos originales y su historial de adendas.</p>
    </div>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?><div class="alert success"><?= esc($mensaje) ?></div><?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>

<?php if ($contratos === []): ?>
    <section class="panel empty-state">No hay contratos registrados.</section>
<?php else: ?>
    <section class="panel table-wrap">
        <table class="data-table">
            <thead><tr><th>Contrato</th><th>Nivel / aula</th><th>Alumnos</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($contratos as $contrato): ?>
                    <?php $estado = (string) ($contrato['estado_contrato'] ?? 'Activo'); ?>
                    <?php $cerrado = in_array(mb_strtolower(trim($estado)), ['cerrado', 'cerrada', 'finalizado', 'finalizada'], true); ?>
                    <tr>
                        <td><?= esc($contrato['numero_contrato'] ?: '#' . $contrato['id']) ?></td>
                        <td><?= esc(trim(($contrato['nivel'] ?? '') . ' ' . ($contrato['aula'] ?? '')) ?: 'Sin especificar') ?></td>
                        <td><?= esc($contrato['cant_alumnos_inicial'] ?? 0) ?></td>
                        <td><span class="status status-<?= $cerrado ? 'denegada' : 'aprobado' ?>"><?= esc($estado) ?></span></td>
                        <td>
                            <div class="inline-actions">
                                <a class="button secondary compact" href="<?= base_url('admin/contratos/' . $contrato['id']) ?>">Ver contrato / adendas</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>

<?= $this->endSection() ?>
