<?= $this->extend('layouts/admin') ?>
<?php $codigoContrato = (string) ($contrato['codigo'] ?: ($contrato['numero_contrato'] ?: '#' . $contrato['id'])); ?>
<?= $this->section('titulo') ?>Contrato <?= esc($codigoContrato) ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php $puedeCrearAjustes = \App\Libraries\AdminAccess::can('contratos.editar'); ?>
<div class="page-heading contract-heading">
    <div>
        <a class="back-link" href="<?= base_url('admin/contratos') ?>">&larr; Volver a contratos</a>
        <h1>Contrato <?= esc($codigoContrato) ?></h1>
        <p>Estado: <?= esc($contrato['estado_contrato'] ?? 'Activo') ?> · Documento original inmutable</p>
    </div>
    <a class="button primary" href="<?= base_url('admin/contratos/' . $contrato['id'] . '/pdf') ?>">Descargar contrato PDF</a>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?><div class="alert success"><?= esc($mensaje) ?></div><?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>

<?php if (\App\Libraries\AdminAccess::isAdmin() && $costoInternoEstimado !== null): ?>
    <section class="panel internal-cost-summary">
        <h2>Costo interno estimado del contrato</h2>
        <p>S/ <?= number_format((float) $costoInternoEstimado, 2) ?> para los servicios vinculados al catálogo y la cantidad inicial de alumnos.</p>
        <small>Dato exclusivo de administración; no forma parte del precio ni altera el documento firmado.</small>
    </section>
<?php endif; ?>

<section class="panel contract-panel">
    <h2>Documento original</h2>
    <dl class="quote-details contract-details">
        <div><dt>Institución</dt><dd><?= esc($contrato['nombre_colegio'] ?? 'Sin especificar') ?></dd></div>
        <div><dt>Promoción / sección</dt><dd><?= esc($contrato['nombre_seccion'] ?? 'Sin especificar') ?></dd></div>
        <div><dt>Persona encargada</dt><dd><?= esc($contrato['persona_encargada'] ?? 'Sin especificar') ?></dd></div>
        <div><dt>Teléfono / WhatsApp</dt><dd><?= esc($contrato['telefono_contacto'] ?? 'Sin especificar') ?></dd></div>
        <div><dt>Nivel / aula</dt><dd><?= esc(trim(($contrato['nivel'] ?? '') . ' ' . ($contrato['aula'] ?? '')) ?: 'Sin especificar') ?></dd></div>
        <div><dt>Código</dt><dd><?= esc($codigoContrato) ?></dd></div>
        <div><dt>Alumnos iniciales</dt><dd><?= esc($contrato['cant_alumnos_inicial'] ?? 0) ?></dd></div>
        <div><dt>Monto original</dt><dd>S/ <?= number_format((float) ($contrato['monto_total'] ?? 0), 2) ?></dd></div>
        <div><dt>Monto vigente</dt><dd><strong>S/ <?= number_format((float) ($montoVigente ?? $contrato['monto_total'] ?? 0), 2) ?></strong></dd></div>
        <div><dt>Saldo original</dt><dd>S/ <?= number_format((float) ($contrato['monto_saldo'] ?? 0), 2) ?></dd></div>
    </dl>
    <?php if (! empty($contrato['observaciones'])): ?><p><strong>Observaciones:</strong> <?= nl2br(esc($contrato['observaciones'])) ?></p><?php endif; ?>
    <h3>Servicios originales</h3>
    <?php if ($contrato['servicios'] === []): ?><p>Sin servicios registrados.</p><?php endif; ?>
    <?php foreach ($contrato['servicios'] as $servicio): ?>
        <p><?= esc($servicio['nombre'] ?? '') ?> · S/ <?= number_format((float) ($servicio['precio_unitario'] ?? 0), 2) ?> por alumno</p>
    <?php endforeach; ?>
    <h3>Agenda original</h3>
    <?php if ($contrato['agenda'] === []): ?><p>Sin agenda registrada.</p><?php endif; ?>
    <?php foreach ($contrato['agenda'] as $sesion): ?><p><?= esc($sesion['fecha'] ?? '') ?> · <?= esc($sesion['detalle'] ?? '') ?></p><?php endforeach; ?>
    <h3>Cuadros originales</h3>
    <?php if ($contrato['cuadros'] === []): ?><p>Sin cuadros registrados.</p><?php endif; ?>
    <?php foreach ($contrato['cuadros'] as $cuadro): ?><p><?= esc($cuadro['detalle'] ?? '') ?> · Cantidad: <?= esc($cuadro['cantidad'] ?? 0) ?></p><?php endforeach; ?>
</section>

<section class="panel contract-panel">
    <h2>Adendas / ajustes de contrato</h2>
    <p>Las modificaciones posteriores se registran aquí sin sobrescribir los datos del documento original.</p>
    <?php if ($ajustes === []): ?>
        <p>No se han registrado adendas.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Código</th><th>Fecha</th><th>Tipo</th><th>Motivo</th><th>Detalle</th><th>Impacto financiero</th></tr></thead>
                <tbody>
                    <?php foreach ($ajustes as $ajuste): ?>
                        <?php $detalle = json_decode((string) $ajuste['detalle_json'], true); ?>
                        <tr>
                            <td><strong><?= esc($ajuste['codigo'] ?: 'ADE-' . $ajuste['id']) ?></strong></td>
                            <td><?= esc($ajuste['creado_en'] ? date('d/m/Y H:i', strtotime($ajuste['creado_en'])) : 'Sin fecha') ?></td>
                            <td><?= esc(ucfirst($ajuste['tipo'])) ?></td>
                            <td><?= esc($ajuste['motivo']) ?></td>
                            <td>
                                <?php if (is_array($detalle) && $ajuste['tipo'] === 'alumnos'): ?>
                                    Variación: <?= esc($detalle['cantidad_alumnos_delta'] ?? 0) ?> alumnos
                                <?php elseif (is_array($detalle) && $ajuste['tipo'] === 'servicio'): ?>
                                    <?= esc(ucfirst($detalle['accion'] ?? '')) ?> <?= esc($detalle['servicio'] ?? '') ?> · <?= esc($detalle['cantidad'] ?? 0) ?> × S/ <?= number_format((float) ($detalle['precio_unitario'] ?? 0), 2) ?>
                                <?php elseif (is_array($detalle)): ?>
                                    <?= esc($detalle['concepto'] ?? '') ?>
                                <?php endif; ?>
                            </td>
                            <td>S/ <?= number_format((float) $ajuste['impacto_financiero'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($puedeCrearAjustes): ?>
        <form class="quote-form contract-adjustment-form" action="<?= base_url('admin/contratos/' . $contrato['id'] . '/ajustes') ?>" method="post">
            <?= csrf_field() ?>
            <h3>Registrar nueva adenda</h3>
            <div class="form-grid">
                <label class="form-field">Tipo de ajuste
                    <select id="adjustment-type" name="tipo" required>
                        <option value="alumnos">Cambio en cantidad de alumnos</option>
                        <option value="servicio">Agregar o retirar servicio</option>
                        <option value="recargo">Recargo</option>
                    </select>
                </label>
                <label class="form-field full">Motivo
                    <input name="motivo" minlength="5" maxlength="500" value="<?= esc(old('motivo')) ?>" required>
                </label>
                <div class="adjustment-fields" data-adjustment="alumnos">
                    <label class="form-field">Variación de alumnos (+ / -)
                        <input class="compact-number" name="cantidad_delta" type="number" min="-10000" max="10000" step="1" onfocus="this.select()" value="<?= esc(old('cantidad_delta')) ?>">
                    </label>
                </div>
                <div class="adjustment-fields" data-adjustment="servicio" hidden>
                    <label class="form-field">Acción
                        <select name="accion_servicio"><option value="agregar">Agregar</option><option value="retirar">Retirar</option></select>
                    </label>
                    <label class="form-field">Servicio
                        <input name="servicio_nombre" maxlength="120">
                    </label>
                    <label class="form-field">Cantidad
                        <input class="compact-number" name="servicio_cantidad" type="number" min="1" max="10000" step="1" onfocus="this.select()" value="1">
                    </label>
                    <label class="form-field">Precio unitario (S/)
                        <input class="compact-number money-number" name="servicio_precio" type="number" min="0" max="99999999.99" step="0.01" onfocus="this.select()">
                    </label>
                </div>
                <div class="adjustment-fields" data-adjustment="recargo" hidden>
                    <label class="form-field">Concepto
                        <input name="concepto_recargo" maxlength="150">
                    </label>
                </div>
                <label class="form-field">Impacto financiero (S/)
                    <input id="adjustment-impact" class="compact-number money-number" name="impacto_financiero" type="number" min="-99999999.99" max="99999999.99" step="0.01" onfocus="this.select()" value="<?= esc(old('impacto_financiero', '0.00')) ?>" required>
                    <small id="adjustment-impact-help">Importe de la adenda; los servicios se calculan automáticamente y al retirarlos se restan.</small>
                </label>
            </div>
            <button class="button primary" type="submit">Registrar adenda</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel contract-panel">
    <h2>Alumnos del contrato</h2>
    <?php if ($alumnos === []): ?><p>Aún no hay alumnos en el padrón.</p><?php endif; ?>
    <?php foreach ($alumnos as $alumno): ?>
        <p><strong><?= esc($alumno['nombre_completo']) ?></strong> · <?= esc($alumno['tipo_registro']) ?> · <?= esc($alumno['estado']) ?><?= $alumno['numero_lista'] ? ' · Lista ' . esc($alumno['numero_lista']) : '' ?></p>
    <?php endforeach; ?>
</section>
<script>
(() => {
    const type = document.getElementById('adjustment-type');
    if (!type) return;
    const impact = document.getElementById('adjustment-impact');
    const serviceAction = document.querySelector('[name="accion_servicio"]');
    const serviceQuantity = document.querySelector('[name="servicio_cantidad"]');
    const servicePrice = document.querySelector('[name="servicio_precio"]');
    const updateImpact = () => {
        const isService = type.value === 'servicio';
        impact.readOnly = isService;
        if (isService) {
            const amount = (Number(serviceQuantity.value) || 0) * (Number(servicePrice.value) || 0);
            impact.value = (serviceAction.value === 'retirar' ? -amount : amount).toFixed(2);
        }
    };
    const showFields = () => {
        document.querySelectorAll('.adjustment-fields').forEach((group) => {
            const active = group.dataset.adjustment === type.value;
            group.hidden = !active;
            group.querySelectorAll('input, select').forEach((field) => {
                field.disabled = !active;
                field.required = active && ['cantidad_delta', 'servicio_nombre', 'servicio_cantidad', 'servicio_precio', 'concepto_recargo'].includes(field.name);
            });
        });
        updateImpact();
    };
    type.addEventListener('change', showFields);
    [serviceAction, serviceQuantity, servicePrice].forEach((field) => field.addEventListener('input', updateImpact));
    serviceAction.addEventListener('change', updateImpact);
    showFields();
})();
</script>
<?= $this->endSection() ?>
