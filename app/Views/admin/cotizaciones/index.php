<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= ! empty($vigentes) ? 'Cotizaciones vigentes' : 'Cotizaciones' ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <h1><?= ! empty($vigentes) ? 'Cotizaciones vigentes' : 'Cotizaciones' ?></h1>
        <p><?= ! empty($vigentes) ? 'Propuestas aprobadas y dentro de su periodo de vigencia.' : 'Revisa las solicitudes recibidas desde el cotizador público.' ?></p>
    </div>
    <?php if (! empty($puedeCrear)): ?>
        <a class="button primary" href="<?= base_url('admin/cotizaciones/nueva') ?>">Crear cotización</a>
    <?php endif; ?>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?>
    <div class="alert success"><?= esc($mensaje) ?></div>
<?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?>
    <div class="alert error"><?= esc($error) ?></div>
<?php endif; ?>

<?php if ($cotizaciones === []): ?>
    <section class="panel empty-state">No hay cotizaciones para mostrar.</section>
<?php else: ?>
    <?php $puedeEditar = \App\Libraries\AdminAccess::can('cotizaciones.editar'); ?>
    <?php $puedeEliminar = \App\Libraries\AdminAccess::can('cotizaciones.eliminar'); ?>
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
                <div class="action-row">
                    <a class="button secondary compact" href="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/ver') ?>">Ver</a>
                    <?php if ($cotizacion['estado'] === 'aprobado' && ! empty($cotizacion['contrato_id'])): ?>
                        <a class="button secondary compact" href="<?= base_url('admin/contratos/' . $cotizacion['contrato_id']) ?>">Abrir contrato</a>
                    <?php elseif ($cotizacion['estado'] === 'aprobado' && \App\Libraries\AdminAccess::isAdmin()): ?>
                        <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/confirmar') ?>" method="post">
                            <?= csrf_field() ?>
                            <button class="button primary compact" type="submit">Crear contrato</button>
                        </form>
                    <?php endif; ?>
                    <?php if (($puedeEditar && in_array($cotizacion['estado'], ['solicitud', 'aprobada_admin'], true)) || (\App\Libraries\AdminAccess::isAdmin() && $cotizacion['estado'] === 'pendiente')): ?>
                        <a class="button secondary compact" href="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/editar') ?>">Editar</a>
                    <?php endif; ?>
                    <?php if ($puedeEliminar && $cotizacion['estado'] !== 'aprobado'): ?>
                        <button class="button danger compact" type="button" data-delete-url="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/eliminar') ?>" data-delete-label="Solicitud #<?= esc($cotizacion['id']) ?>">Eliminar</button>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (! empty($puedeEliminar)): ?>
    <dialog id="delete-dialog" aria-labelledby="delete-title">
        <form id="delete-form" method="post">
            <?= csrf_field() ?>
            <h2 id="delete-title">Confirmar eliminación</h2>
            <p id="delete-message">La acción no se puede deshacer.</p>
            <div class="dialog-actions">
                <button class="button secondary" type="button" data-close-dialog>Cancelar</button>
                <button class="button danger" type="submit">Confirmar eliminación</button>
            </div>
        </form>
    </dialog>
    <script>
        const deleteDialog = document.getElementById('delete-dialog');
        const deleteForm = document.getElementById('delete-form');
        const deleteMessage = document.getElementById('delete-message');

        document.querySelectorAll('[data-delete-url]').forEach((button) => {
            button.addEventListener('click', () => {
                deleteForm.action = button.dataset.deleteUrl;
                deleteMessage.textContent = `¿Eliminar ${button.dataset.deleteLabel}? Esta acción no se puede deshacer.`;
                deleteDialog.showModal();
            });
        });
        document.querySelector('[data-close-dialog]')?.addEventListener('click', () => deleteDialog.close());
    </script>
<?php endif; ?>
<?= $this->endSection() ?>
