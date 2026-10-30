<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= ! empty($soloLectura) ? 'Ver' : 'Editar' ?> cotización #<?= esc($cotizacion['id']) ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php $estadoVisible = $cotizacion['estado'] === 'aprobada_admin' ? 'Aprobada por el Admin' : ucfirst($cotizacion['estado']); ?>
<?php $soloLectura = $soloLectura ?? false; ?>
<?php $administrador = \App\Libraries\AdminAccess::isAdmin(); ?>
<div class="page-heading">
    <div>
        <a class="back-link" href="<?= base_url('admin/cotizaciones') ?>">&larr; Volver a solicitudes</a>
        <h1>Cotización #<?= esc($cotizacion['id']) ?></h1>
    </div>
    <span class="status status-<?= esc($cotizacion['estado']) ?>"><?= esc($estadoVisible) ?></span>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?>
    <div class="alert success"><?= esc($mensaje) ?></div>
<?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?>
    <div class="alert error"><?= esc($error) ?></div>
<?php endif; ?>

<section class="panel request-summary">
    <h2>Datos de la cotización</h2>
    <p><strong>Colegio / institución:</strong> <?= esc($cotizacion['nombre_colegio']) ?></p>
    <p><strong>Cantidad de estudiantes:</strong> <?= esc($cotizacion['cantidad_estudiantes']) ?></p>
    <h3>Servicios cotizados</h3>
    <?php foreach ($cotizacion['servicios'] as $servicio): ?>
        <p><?= esc($servicio['nombre']) ?> · S/ <?= number_format((float) $servicio['precio_unitario'], 2) ?> por estudiante</p>
    <?php endforeach; ?>
</section>

<?php if (! $soloLectura && (in_array($cotizacion['estado'], ['solicitud', 'aprobada_admin'], true) || ($administrador && $cotizacion['estado'] === 'pendiente'))): ?>
    <form class="panel quote-form" action="<?= base_url('admin/cotizaciones/' . $cotizacion['id']) ?>" method="post">
        <?= csrf_field() ?>
        <h2><?= $administrador ? 'Editar cotización' : 'Solicitar cambios de cotización' ?></h2>
        <label for="cantidad_estudiantes">Cantidad de estudiantes</label>
        <input id="cantidad_estudiantes" name="cantidad_estudiantes" type="number" min="1" max="10000" value="<?= esc(old('cantidad_estudiantes', $cotizacion['cantidad_estudiantes'])) ?>" required>
        <div id="service-list">
            <?php foreach ($cotizacion['servicios'] as $indice => $servicio): ?>
                <div class="service-row">
                    <label>Servicio
                        <input name="servicios[<?= esc($indice) ?>][nombre]" maxlength="120" value="<?= esc(old('servicios.' . $indice . '.nombre', $servicio['nombre'])) ?>" required>
                    </label>
                    <label>Precio por estudiante (S/)
                        <input name="servicios[<?= esc($indice) ?>][precio_unitario]" type="number" min="0" step="0.01" value="<?= esc(old('servicios.' . $indice . '.precio_unitario', $servicio['precio_unitario'])) ?>" required>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="button secondary" id="add-service" type="button">Agregar servicio sugerido</button>
        <p class="quote-total">Precio por estudiante: S/ <?= number_format((float) $cotizacion['precio_unitario'], 2) ?> · Total: S/ <?= number_format((float) $cotizacion['total_seccion'], 2) ?></p>
        <button class="button primary" type="submit"><?= $administrador ? 'Guardar cambios' : 'Enviar solicitud al administrador' ?></button>
    </form>

    <?php if (in_array($cotizacion['estado'], ['solicitud', 'aprobada_admin'], true)): ?>
        <?php if ($administrador): ?>
            <form class="panel workflow-action" action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/activar') ?>" method="post">
                <?= csrf_field() ?>
                <label for="dias_vigencia">Días de vigencia de la cotización (1–365)</label>
                <input id="dias_vigencia" name="dias_vigencia" type="number" min="1" max="365" value="<?= esc(old('dias_vigencia', 15)) ?>" required>
                <button class="button primary" type="submit">Pasar a cotización vigente</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
<?php elseif ($cotizacion['estado'] === 'pendiente'): ?>
    <section class="panel workflow-action">
        <h2>Cotización enviada al cliente</h2>
        <p>Vigencia: <?= esc($cotizacion['dias_vigencia']) ?> días, hasta <?= esc(date('d/m/Y', strtotime($cotizacion['vence_en']))) ?>.</p>
        <?php if ($administrador): ?>
            <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/pdf') ?>" method="post">
                <?= csrf_field() ?>
                <button class="button secondary" type="submit">Descargar cotización PDF</button>
            </form>
        <?php endif; ?>
        <?php if ($administrador && ! $soloLectura): ?>
            <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/confirmar') ?>" method="post">
                <?= csrf_field() ?>
                <button class="button primary" type="submit">Confirmar cliente y crear contrato</button>
            </form>
        <?php endif; ?>
    </section>
<?php elseif ($cotizacion['estado'] === 'aprobado'): ?>
    <section class="panel workflow-action">
        <h2>Cotización confirmada por el cliente</h2>
        <?php if (! empty($contratoId)): ?>
            <a class="button primary" href="<?= base_url('admin/contratos/' . $contratoId) ?>">Abrir contrato</a>
        <?php elseif ($administrador): ?>
            <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/confirmar') ?>" method="post">
                <?= csrf_field() ?>
                <button class="button primary" type="submit">Crear contrato</button>
            </form>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="panel workflow-action">
        <?php if ($cotizacion['estado'] === 'solicitud'): ?>
            <h2>Solicitud pendiente de revisión</h2>
        <?php else: ?>
            <h2>Cotización aprobada por el administrador</h2>
            <p>Está lista para generar y enviar el PDF.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>

    <?php if (! $soloLectura && $administrador && in_array($cotizacion['estado'], ['solicitud', 'aprobada_admin', 'pendiente'], true)): ?>
        <dialog id="save-dialog" aria-labelledby="save-title">
            <h2 id="save-title">Confirmar cambios</h2>
            <p>Los cambios se aplicarán inmediatamente a esta cotización.</p>
            <div class="dialog-actions">
                <button class="button secondary" type="button" id="cancel-save">Seguir editando</button>
                <button class="button primary" type="button" id="confirm-save">Confirmar y guardar</button>
            </div>
        </dialog>
    <?php endif; ?>

<script>
    const serviceList = document.getElementById('service-list');
    const addServiceButton = document.getElementById('add-service');

    if (serviceList && addServiceButton) {
        addServiceButton.addEventListener('click', () => {
            const index = serviceList.querySelectorAll('.service-row').length;
            const row = document.createElement('div');
            row.className = 'service-row';
            row.innerHTML = `
                <label>Servicio
                    <input name="servicios[${index}][nombre]" maxlength="120" required>
                </label>
                <label>Precio por estudiante (S/)
                    <input name="servicios[${index}][precio_unitario]" type="number" min="0" step="0.01" value="0" required>
                </label>`;
            serviceList.append(row);
        });
    }

    const quoteForm = document.querySelector('.quote-form');
    const saveDialog = document.getElementById('save-dialog');
    if (quoteForm && saveDialog) {
        let confirmed = false;
        quoteForm.addEventListener('submit', (event) => {
            if (confirmed) return;
            event.preventDefault();
            saveDialog.showModal();
        });
        document.getElementById('cancel-save').addEventListener('click', () => saveDialog.close());
        document.getElementById('confirm-save').addEventListener('click', () => {
            confirmed = true;
            saveDialog.close();
            quoteForm.requestSubmit();
        });
    }
</script>
<?= $this->endSection() ?>
