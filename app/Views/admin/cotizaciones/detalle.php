<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Detalle de cotización #<?= esc($cotizacion['id']) ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php
$estadoVisible = $cotizacion['estado'] === 'aprobada_admin' ? 'Aprobada por el administrador' : ucfirst($cotizacion['estado']);
$administrador = \App\Libraries\AdminAccess::isAdmin();
$puedeEliminar = \App\Libraries\AdminAccess::isAdmin() || \App\Libraries\AdminAccess::can('cotizaciones.eliminar');
?>
<div class="page-heading">
    <div>
        <a class="back-link" href="<?= base_url('admin/cotizaciones') ?>">&larr; Volver a cotizaciones</a>
        <h1>Detalle de cotización #<?= esc($cotizacion['id']) ?></h1>
    </div>
    <span class="status status-<?= esc($cotizacion['estado']) ?>"><?= esc($estadoVisible) ?></span>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?><div class="alert success"><?= esc($mensaje) ?></div><?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>

<section class="panel request-summary">
    <h2>Datos recibidos</h2>
    <dl class="quote-details">
        <div><dt>Institución</dt><dd><?= esc($cotizacion['nombre_colegio']) ?></dd></div>
        <div><dt>Promoción / sección</dt><dd><?= esc($cotizacion['nombre_seccion'] ?: 'No especificada') ?></dd></div>
        <div><dt>Cantidad de alumnos</dt><dd><?= esc($cotizacion['cantidad_estudiantes']) ?></dd></div>
        <div><dt>Persona encargada o tesorera</dt><dd><?= esc($cotizacion['persona_encargada'] ?: 'Sin dato') ?></dd></div>
        <div><dt>Teléfono / WhatsApp</dt><dd><?= esc($cotizacion['telefono_contacto'] ?: 'Sin dato') ?></dd></div>
        <div><dt>Fecha de solicitud</dt><dd><?= esc($cotizacion['created_at'] ? date('d/m/Y H:i', strtotime($cotizacion['created_at'])) : 'Sin fecha') ?></dd></div>
        <div><dt>Precio por alumno</dt><dd>S/ <?= number_format((float) $cotizacion['precio_unitario'], 2) ?></dd></div>
        <div><dt>Total estimado</dt><dd>S/ <?= number_format((float) $cotizacion['total_seccion'], 2) ?></dd></div>
    </dl>
    <h3>Servicios seleccionados</h3>
    <?php if ($cotizacion['servicios'] === []): ?>
        <p>No se registraron servicios.</p>
    <?php else: ?>
        <ul class="quote-service-list">
            <?php foreach ($cotizacion['servicios'] as $servicio): ?>
                <li><?= esc($servicio['nombre'] ?? '') ?> · S/ <?= number_format((float) ($servicio['precio_unitario'] ?? 0), 2) ?> por alumno</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($administrador && in_array($cotizacion['estado'], ['solicitud', 'aprobada_admin'], true) && $cotizacion['servicios'] !== []): ?>
        <form class="quote-form" action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/precios') ?>" method="post">
            <?= csrf_field() ?>
            <h3>Precios de cotización (independientes del precio interno)</h3>
            <div class="service-selection">
                <?php foreach ($cotizacion['servicios'] as $indice => $servicio): ?>
                    <label class="service-selection-row">
                        <span><?= esc($servicio['nombre'] ?? '') ?></span>
                        <input name="precios[<?= (int) $indice ?>]" type="number" min="0" max="9999999999.99" step="0.01" value="<?= esc(old('precios.' . $indice, $servicio['precio_unitario'] ?? '0.00')) ?>" required>
                    </label>
                <?php endforeach; ?>
            </div>
            <button class="button primary" type="submit">Guardar precios</button>
        </form>
    <?php endif; ?>
    <?php if ($cotizacion['vence_en']): ?>
        <p><strong>Vigente hasta:</strong> <?= esc(date('d/m/Y', strtotime($cotizacion['vence_en']))) ?></p>
    <?php endif; ?>
</section>

<section class="panel workflow-action">
    <h2>Acciones comerciales</h2>
    <div class="action-row">
        <?php if ($administrador && in_array($cotizacion['estado'], ['solicitud', 'aprobada_admin'], true)): ?>
            <button class="button primary" type="button" id="open-validity">Pasar a Cotización Vigente</button>
        <?php endif; ?>

        <?php if ($contratoId !== null): ?>
            <a class="button secondary" href="<?= base_url('admin/contratos/' . $contratoId) ?>">Abrir contrato asociado</a>
        <?php elseif ($administrador): ?>
            <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/contrato') ?>" method="post" onsubmit="return confirm('¿Pasar esta solicitud a contrato?');">
                <?= csrf_field() ?>
                <button class="button primary" type="submit">Pasar a Contrato</button>
            </form>
        <?php endif; ?>

        <?php if ($puedeEliminar && $contratoId === null): ?>
            <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/eliminar') ?>" method="post" onsubmit="return confirm('¿Eliminar esta cotización? Esta acción no se puede deshacer.');">
                <?= csrf_field() ?>
                <button class="button danger" type="submit">Eliminar</button>
            </form>
        <?php endif; ?>

        <?php if ($administrador && $cotizacion['estado'] === 'pendiente'): ?>
            <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/pdf') ?>" method="post">
                <?= csrf_field() ?>
                <button class="button secondary" type="submit">Descargar cotización PDF</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if ($administrador && in_array($cotizacion['estado'], ['solicitud', 'aprobada_admin'], true) && (float) $cotizacion['total_seccion'] <= 0): ?>
        <p class="alert error">La vigencia requiere un precio total mayor que cero. Configura los precios de cotización.</p>
    <?php endif; ?>
</section>

<?php if ($administrador && in_array($cotizacion['estado'], ['solicitud', 'aprobada_admin'], true)): ?>
    <dialog id="validity-dialog" aria-labelledby="validity-title">
        <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/activar') ?>" method="post">
            <?= csrf_field() ?>
            <h2 id="validity-title">Definir vigencia</h2>
            <label for="dias_vigencia">Días de vigencia (1–365)</label>
            <input id="dias_vigencia" name="dias_vigencia" type="number" min="1" max="365" value="<?= esc(old('dias_vigencia', 15)) ?>" required>
            <div class="dialog-actions">
                <button class="button secondary" type="button" id="close-validity">Cancelar</button>
                <button class="button primary" type="submit">Confirmar vigencia</button>
            </div>
        </form>
    </dialog>
    <script>
        const validityDialog = document.getElementById('validity-dialog');
        document.getElementById('open-validity').addEventListener('click', () => validityDialog.showModal());
        document.getElementById('close-validity').addEventListener('click', () => validityDialog.close());
    </script>
<?php endif; ?>
<?= $this->endSection() ?>
