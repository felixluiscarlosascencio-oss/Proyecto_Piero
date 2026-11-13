<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Contrato <?= esc($contrato['numero_contrato'] ?: '#' . $contrato['id']) ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php $puedeCrearAjustes = \App\Libraries\AdminAccess::can('contratos.editar'); ?>
<div class="page-heading">
    <div>
        <a class="back-link" href="<?= base_url('admin/contratos') ?>">&larr; Volver a contratos</a>
        <h1>Contrato <?= esc($contrato['numero_contrato'] ?: '#' . $contrato['id']) ?></h1>
        <p>Estado: <?= esc($contrato['estado_contrato'] ?? 'Activo') ?> · Documento original inmutable</p>
    </div>
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

<section class="panel">
    <h2>Documento original</h2>
    <dl class="quote-details">
        <div><dt>Institución</dt><dd><?= esc($contrato['nombre_colegio'] ?? 'Sin especificar') ?></dd></div>
        <div><dt>Promoción / sección</dt><dd><?= esc($contrato['nombre_seccion'] ?? 'Sin especificar') ?></dd></div>
        <div><dt>Persona encargada</dt><dd><?= esc($contrato['persona_encargada'] ?? 'Sin especificar') ?></dd></div>
        <div><dt>Teléfono / WhatsApp</dt><dd><?= esc($contrato['telefono_contacto'] ?? 'Sin especificar') ?></dd></div>
        <div><dt>Nivel / aula</dt><dd><?= esc(trim(($contrato['nivel'] ?? '') . ' ' . ($contrato['aula'] ?? '')) ?: 'Sin especificar') ?></dd></div>
        <div><dt>Alumnos iniciales</dt><dd><?= esc($contrato['cant_alumnos_inicial'] ?? 0) ?></dd></div>
        <div><dt>Monto original</dt><dd>S/ <?= number_format((float) ($contrato['monto_total'] ?? 0), 2) ?></dd></div>
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

<section class="panel">
    <h2>Adendas / ajustes de contrato</h2>
    <p>Las modificaciones posteriores se registran aquí sin sobrescribir los datos del documento original.</p>
    <?php if ($ajustes === []): ?>
        <p>No se han registrado adendas.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Fecha</th><th>Tipo</th><th>Motivo</th><th>Detalle</th><th>Impacto financiero</th></tr></thead>
                <tbody>
                    <?php foreach ($ajustes as $ajuste): ?>
                        <?php $detalle = json_decode((string) $ajuste['detalle_json'], true); ?>
                        <tr>
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
        <form class="quote-form" action="<?= base_url('admin/contratos/' . $contrato['id'] . '/ajustes') ?>" method="post">
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
                        <input name="cantidad_delta" type="number" min="-10000" max="10000" value="<?= esc(old('cantidad_delta')) ?>">
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
                        <input name="servicio_cantidad" type="number" min="1" max="10000" value="1">
                    </label>
                    <label class="form-field">Precio unitario (S/)
                        <input name="servicio_precio" type="number" min="0" max="99999999.99" step="0.01">
                    </label>
                </div>
                <div class="adjustment-fields" data-adjustment="recargo" hidden>
                    <label class="form-field">Concepto
                        <input name="concepto_recargo" maxlength="150">
                    </label>
                </div>
                <label class="form-field">Impacto financiero (S/)
                    <input name="impacto_financiero" type="number" min="-99999999.99" max="99999999.99" step="0.01" value="<?= esc(old('impacto_financiero', '0.00')) ?>" required>
                    <small>Importe de la adenda; negativo para una reducción. Al retirar un servicio se registra como negativo automáticamente.</small>
                </label>
            </div>
            <button class="button primary" type="submit">Registrar adenda</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel">
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
    const showFields = () => {
        document.querySelectorAll('.adjustment-fields').forEach((group) => {
            const active = group.dataset.adjustment === type.value;
            group.hidden = !active;
            group.querySelectorAll('input, select').forEach((field) => {
                field.disabled = !active;
                field.required = active && ['cantidad_delta', 'servicio_nombre', 'servicio_cantidad', 'servicio_precio', 'concepto_recargo'].includes(field.name);
            });
        });
    };
    type.addEventListener('change', showFields);
    showFields();
})();
</script>
<?= $this->endSection() ?>
