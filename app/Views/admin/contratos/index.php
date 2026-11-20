<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Contratos<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <h1>Contratos</h1>
        <p>Documentos originales, montos y adendas registradas.</p>
    </div>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?><div class="alert success"><?= esc($mensaje) ?></div><?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>

<?php if ($contratos === []): ?>
    <section class="panel empty-state">No hay contratos registrados.</section>
<?php else: ?>
    <section class="panel table-wrap">
        <table class="data-table">
            <thead><tr><th>Contrato</th><th>Institución</th><th>Promoción / aula</th><th>Alumnos</th><th>Monto</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($contratos as $contrato): ?>
                    <?php
                    $estado = (string) ($contrato['estado_contrato'] ?? 'Activo');
                    $cerrado = in_array(mb_strtolower(trim($estado)), ['cerrado', 'cerrada', 'finalizado', 'finalizada'], true);
                    $codigo = (string) ($contrato['codigo'] ?: ($contrato['numero_contrato'] ?: '#' . $contrato['id']));
                    ?>
                    <tr>
                        <td><strong><?= esc($codigo) ?></strong><br><small><?= esc(! empty($contrato['creado_en']) ? date('d/m/Y', strtotime($contrato['creado_en'])) : 'Sin fecha') ?></small></td>
                        <td><?= esc($contrato['nombre_colegio'] ?: 'Sin especificar') ?></td>
                        <td><?= esc(trim(($contrato['nivel'] ?? '') . ' ' . ($contrato['aula'] ?? '')) ?: 'Sin especificar') ?></td>
                        <td><?= esc($contrato['cant_alumnos_inicial'] ?? 0) ?></td>
                        <td>S/ <?= number_format((float) ($contrato['monto_total'] ?? 0), 2) ?></td>
                        <td><span class="status status-<?= $cerrado ? 'vencida' : 'aceptada' ?>"><?= esc($estado) ?></span></td>
                        <td>
                            <div class="icon-actions">
                                <a class="icon-button" href="<?= base_url('admin/contratos/' . $contrato['id']) ?>" title="Ver contrato y adendas" aria-label="Ver contrato <?= esc($codigo) ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                </a>
                                <a class="icon-button" href="<?= base_url('admin/contratos/' . $contrato['id'] . '/pdf') ?>" title="Descargar contrato" aria-label="Descargar contrato <?= esc($codigo) ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>

<?= $this->endSection() ?>
