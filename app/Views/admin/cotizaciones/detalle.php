<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= esc(\App\Models\CotizacionModel::codigoVisible($cotizacion)) ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php
$codigo = \App\Models\CotizacionModel::codigoVisible($cotizacion);
$estadoVisible = \App\Models\CotizacionModel::etiquetaEstado($estadoEfectivo);
$revision = (string) ($cotizacion['estado_revision'] ?? \App\Models\CotizacionModel::REVISION_SIN_REVISAR);
$revisionVisible = \App\Models\CotizacionModel::etiquetaRevision($revision);
$servicios = $cotizacion['servicios'] ?? [];
$fechaSolicitud = ! empty($cotizacion['created_at']) ? date('d/m/Y H:i', strtotime($cotizacion['created_at'])) : 'Sin fecha';
$venceEn = $cotizacion['vence_en'] ?? null;
$contratoId = $contrato['id'] ?? null;
?>
<div class="page-heading quote-detail-heading">
    <div>
        <a class="back-link" href="<?= base_url('admin/cotizaciones') ?>">&larr; Volver a cotizaciones</a>
        <h1><?= esc($codigo) ?></h1>
        <p><?= esc($cotizacion['nombre_colegio']) ?> · <?= $esSolicitud ? 'Solicitud recibida' : 'Cotización formal' ?></p>
    </div>
    <div class="heading-badges">
        <span class="status status-<?= esc($estadoEfectivo) ?>"><?= esc($estadoVisible) ?></span>
        <span class="status status-revision-<?= esc(strtolower($revision)) ?>"><?= esc($revisionVisible) ?></span>
        <?php if (! $esSolicitud): ?><span class="status status-version">Versión <?= (int) $cotizacion['version'] ?></span><?php endif; ?>
    </div>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?><div class="alert success"><?= esc($mensaje) ?></div><?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>

<section class="panel request-summary">
    <div class="panel-section-heading">
        <div>
            <h2><?= $esSolicitud ? 'Datos de la solicitud' : 'Datos de la cotización' ?></h2>
            <p><?= $esSolicitud ? 'Información recibida desde el cotizador público.' : 'Propuesta formal enviada al cliente.' ?></p>
        </div>
        <?php if (! $esSolicitud): ?>
            <span class="quote-id"><?= esc($cotizacion['codigo_cotizacion']) ?></span>
        <?php endif; ?>
    </div>
    <dl class="quote-details">
        <div><dt><?= $esSolicitud ? 'Código de solicitud' : 'Código de cotización' ?></dt><dd><?= esc($esSolicitud ? ($cotizacion['codigo_solicitud'] ?: 'Sin código') : $cotizacion['codigo_cotizacion']) ?></dd></div>
        <div><dt>Fecha de registro</dt><dd><?= esc($fechaSolicitud) ?></dd></div>
        <div><dt>Institución</dt><dd><?= esc($cotizacion['nombre_colegio']) ?></dd></div>
        <div><dt>Promoción / sección</dt><dd><?= esc($cotizacion['nombre_seccion'] ?: 'No especificada') ?></dd></div>
        <div><dt>Cantidad de alumnos</dt><dd><?= esc($cotizacion['cantidad_estudiantes']) ?></dd></div>
        <div><dt>Persona encargada</dt><dd><?= esc($cotizacion['persona_encargada'] ?: 'Sin dato') ?></dd></div>
        <div><dt>Teléfono / WhatsApp</dt><dd><?= esc($cotizacion['telefono_contacto'] ?: 'Sin dato') ?></dd></div>
        <div><dt>Distrito / provincia</dt><dd><?= esc(trim(($cotizacion['distrito'] ?? '') . ' / ' . ($cotizacion['provincia'] ?? ''), ' /') ?: 'Sin dato') ?></dd></div>
        <div><dt>Nivel</dt><dd><?= esc($cotizacion['nivel'] ?: 'Sin dato') ?></dd></div>
        <div><dt>Servicio solicitado</dt><dd><?= esc($cotizacion['tipo_servicio'] ?: 'Sin dato') ?></dd></div>
        <?php if ($esSolicitud): ?>
            <div><dt>Revisión administrativa</dt><dd><?= esc($revisionVisible) ?></dd></div>
            <div><dt>Responsable</dt><dd><?= esc($cotizacion['responsable']) ?></dd></div>
        <?php else: ?>
            <div><dt>Emitida</dt><dd><?= esc($cotizacion['created_at'] ? date('d/m/Y H:i', strtotime($cotizacion['created_at'])) : 'Sin fecha') ?></dd></div>
            <div><dt>Vigente hasta</dt><dd><?= $venceEn ? esc(date('d/m/Y', strtotime($venceEn))) : 'Sin vigencia definida' ?></dd></div>
            <div><dt>Responsable</dt><dd><?= esc($cotizacion['responsable']) ?></dd></div>
            <?php if (! empty($cotizacion['respondido_en'])): ?>
                <div><dt>Respuesta del cliente</dt><dd><?= esc(date('d/m/Y H:i', strtotime($cotizacion['respondido_en']))) ?></dd></div>
            <?php endif; ?>
        <?php endif; ?>
        <div><dt>Precio por alumno</dt><dd>S/ <?= number_format((float) $cotizacion['precio_unitario'], 2) ?></dd></div>
        <div><dt>Total</dt><dd><strong>S/ <?= number_format((float) $cotizacion['total_seccion'], 2) ?></strong></dd></div>
    </dl>

    <h3>Servicios <?= $esSolicitud ? 'solicitados' : 'cotizados' ?></h3>
    <?php if ($servicios === []): ?>
        <p>No se registraron servicios.</p>
    <?php else: ?>
        <ul class="quote-service-list">
            <?php foreach ($servicios as $servicio): ?>
                <?php $precio = (float) ($servicio['precio_unitario'] ?? 0); ?>
                <li><strong><?= esc($servicio['nombre'] ?? '') ?></strong> · <?= $precio > 0 ? 'S/ ' . number_format($precio, 2) . ' por alumno' : 'Precio por definir' ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (! empty($cotizacion['observaciones'])): ?>
        <h3>Observaciones</h3>
        <p><?= nl2br(esc($cotizacion['observaciones'])) ?></p>
    <?php endif; ?>

    <?php if (! $esSolicitud && ! empty($cotizacion['motivo_decision'])): ?>
        <h3>Motivo del rechazo</h3>
        <p><?= nl2br(esc($cotizacion['motivo_decision'])) ?></p>
    <?php endif; ?>
</section>

<?php if ($puedeEditarPrecios): ?>
    <section class="panel quote-form">
        <h2>Definir precios y emitir la cotización formal</h2>
        <p>Al guardar se generará un código <strong>COT</strong> y una versión 1. Los precios quedarán bloqueados; los cambios posteriores se harán con una nueva versión.</p>
        <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/precios') ?>" method="post">
            <?= csrf_field() ?>
            <div class="service-selection">
                <?php foreach ($servicios as $indice => $servicio): ?>
                    <label class="service-selection-row">
                        <span><?= esc($servicio['nombre'] ?? '') ?></span>
                        <?php $precioPorDefecto = (float) ($servicio['precio_unitario'] ?? 0) > 0 ? $servicio['precio_unitario'] : ($servicio['precio_interno'] ?? '0.00'); ?>
                        <input name="precios[<?= (int) $indice ?>]" type="number" min="1" max="<?= esc($servicio['precio_interno'] ?? '9999999999.99') ?>" step="1" onfocus="this.select()" value="<?= esc(old('precios.' . $indice, $precioPorDefecto)) ?>" required>
                    </label>
                <?php endforeach; ?>
            </div>
            <button class="button primary" type="submit">Guardar y generar cotización</button>
        </form>
    </section>
<?php endif; ?>

<?php if ($esSolicitud && $puedeMarcarRevision): ?>
    <section class="panel workflow-action">
        <h2>Revisión administrativa</h2>
        <p>Marca la solicitud como en revisión mientras verificas los datos con el cliente.</p>
        <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/revision') ?>" method="post">
            <?= csrf_field() ?>
            <button class="button secondary" type="submit">Marcar en revisión</button>
        </form>
    </section>
<?php endif; ?>

<?php if (! $esSolicitud): ?>
    <section class="panel workflow-action quote-actions-panel">
        <div class="panel-section-heading">
            <div>
                <h2>Acciones comerciales</h2>
                <p>El historial y los documentos se conservan aunque cambie el estado.</p>
            </div>
            <?php if ($contratoId !== null): ?>
                <a class="button secondary compact" href="<?= base_url('admin/contratos/' . $contratoId) ?>">Abrir contrato <?= esc($contrato['codigo'] ?? ('#' . $contratoId)) ?></a>
            <?php endif; ?>
        </div>
        <div class="action-row">
            <?php if ($puedeDescargarPdf): ?>
                <a class="button secondary" href="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/pdf') ?>">Descargar PDF</a>
            <?php endif; ?>
            <?php if ($puedeCrearVersion): ?>
                <a class="button secondary" href="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/duplicar') ?>">Nueva versión</a>
            <?php endif; ?>
            <?php if ($puedeActivar): ?>
                <button class="button primary" type="button" id="open-validity">Enviar y definir vigencia</button>
            <?php endif; ?>
            <?php if ($puedeResponder): ?>
                <button class="button secondary" type="button" id="open-accept">Registrar aceptación</button>
                <button class="button danger" type="button" id="open-reject">Registrar rechazo</button>
            <?php endif; ?>
            <?php if ($puedePasarContrato): ?>
                <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/contrato') ?>" method="post" onsubmit="return confirm('¿Generar el contrato a partir de esta cotización aceptada?');">
                    <?= csrf_field() ?>
                    <button class="button primary" type="submit">Generar contrato</button>
                </form>
            <?php endif; ?>
            <?php if ($puedeEliminar): ?>
                <button class="button danger" type="button" id="open-delete">Eliminar</button>
            <?php endif; ?>
        </div>
        <?php if ($puedeActivar): ?>
            <p class="hint">La cotización quedará vigente y las versiones anteriores se conservarán como históricas.</p>
        <?php elseif ($puedeResponder): ?>
            <p class="hint">Registra la respuesta del cliente para habilitar la conversión a contrato.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if (! $esSolicitud && count($versiones) > 1): ?>
    <section class="panel quote-history">
        <div class="panel-section-heading">
            <div>
                <h2>Historial de versiones</h2>
                <p>La versión 1 siempre es la primera. Cada cambio genera la siguiente sin renumerar.</p>
            </div>
            <span class="chart-unit"><?= count($versiones) ?> versiones</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Versión</th>
                        <th>Código</th>
                        <th>Fecha</th>
                        <th>Responsable</th>
                        <th>Servicios</th>
                        <th>Precio / alumno</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versiones as $version): ?>
                        <?php
                        $esActual = (int) $version['id'] === (int) $cotizacion['id'];
                        $nombres = array_filter(array_column($version['servicios'], 'nombre'), 'is_string');
                        ?>
                        <tr<?= $esActual ? ' class="is-current-row"' : '' ?>>
                            <td><strong>v<?= (int) $version['version'] ?></strong></td>
                            <td><?= esc($version['codigo_cotizacion']) ?></td>
                            <td><?= esc($version['created_at'] ? date('d/m/Y H:i', strtotime($version['created_at'])) : 'Sin fecha') ?></td>
                            <td><?= esc($version['responsable'] ?? 'Sin responsable') ?></td>
                            <td class="cell-wrap"><?= esc($nombres !== [] ? implode(', ', $nombres) : 'Sin servicios') ?></td>
                            <td>S/ <?= number_format((float) $version['precio_unitario'], 2) ?></td>
                            <td>S/ <?= number_format((float) $version['total_seccion'], 2) ?></td>
                            <td><span class="status status-<?= esc($version['estado_efectivo']) ?>"><?= esc(\App\Models\CotizacionModel::etiquetaEstado($version['estado_efectivo'])) ?></span></td>
                            <td>
                                <div class="icon-actions">
                                    <a class="icon-button" href="<?= base_url('admin/cotizaciones/' . $version['id'] . '/ver') ?>" title="Ver versión <?= (int) $version['version'] ?>" aria-label="Ver versión <?= (int) $version['version'] ?>">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                    </a>
                                    <?php if ((float) $version['total_seccion'] > 0): ?>
                                        <a class="icon-button" href="<?= base_url('admin/cotizaciones/' . $version['id'] . '/pdf') ?>" title="Descargar PDF de la versión <?= (int) $version['version'] ?>" aria-label="Descargar PDF de la versión <?= (int) $version['version'] ?>">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if (($eventos ?? []) !== []): ?>
    <section class="panel quote-timeline">
        <h2>Actividad reciente</h2>
        <ol class="timeline">
            <?php foreach ($eventos as $evento): ?>
                <?php
                $detalle = json_decode((string) ($evento['detalle'] ?? ''), true);
                $etiquetas = [
                    'solicitud_recibida' => 'Solicitud recibida',
                    'revision_iniciada' => 'Revisión iniciada',
                    'cotizacion_generada' => 'Cotización formal generada',
                    'cotizacion_creada' => 'Cotización creada',
                    'cotizacion_enviada' => 'Cotización enviada al cliente',
                    'cotizacion_aceptada' => 'Cotización aceptada',
                    'cotizacion_rechazada' => 'Cotización rechazada',
                    'contrato_creado' => 'Contrato generado',
                ];
                ?>
                <li>
                    <strong><?= esc($etiquetas[$evento['tipo']] ?? ucfirst(str_replace('_', ' ', (string) $evento['tipo']))) ?></strong>
                    <span><?= esc($evento['creado_en'] ? date('d/m/Y H:i', strtotime($evento['creado_en'])) : 'Sin fecha') ?></span>
                    <?php if (is_array($detalle) && ! empty($detalle['motivo'])): ?><small>Motivo: <?= esc($detalle['motivo']) ?></small><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
<?php endif; ?>

<?php if ($puedeActivar): ?>
    <dialog id="validity-dialog" aria-labelledby="validity-title">
        <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/activar') ?>" method="post">
            <?= csrf_field() ?>
            <h2 id="validity-title">Enviar cotización</h2>
            <p>Define el periodo de vigencia. Al enviarla, las versiones anteriores quedarán reemplazadas.</p>
            <label for="dias_vigencia">Días de vigencia (1–365)</label>
            <input id="dias_vigencia" name="dias_vigencia" type="number" min="1" max="365" step="1" onfocus="this.select()" value="<?= esc(old('dias_vigencia', 15)) ?>" required>
            <div class="dialog-actions">
                <button class="button secondary" type="button" data-close-dialog>Cancelar</button>
                <button class="button primary" type="submit">Confirmar envío</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>

<?php if ($puedeResponder): ?>
    <dialog id="accept-dialog" aria-labelledby="accept-title">
        <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/aceptar') ?>" method="post">
            <?= csrf_field() ?>
            <h2 id="accept-title">Registrar aceptación</h2>
            <p>Confirma que el cliente aceptó la cotización <strong><?= esc($codigo) ?></strong>. Después podrás generar el contrato.</p>
            <div class="dialog-actions">
                <button class="button secondary" type="button" data-close-dialog>Cancelar</button>
                <button class="button primary" type="submit">Confirmar aceptación</button>
            </div>
        </form>
    </dialog>
    <dialog id="reject-dialog" aria-labelledby="reject-title">
        <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/rechazar') ?>" method="post">
            <?= csrf_field() ?>
            <h2 id="reject-title">Registrar rechazo</h2>
            <label for="motivo_decision">Motivo del rechazo (5–1000 caracteres)</label>
            <textarea id="motivo_decision" name="motivo_decision" minlength="5" maxlength="1000" required><?= esc(old('motivo_decision')) ?></textarea>
            <div class="dialog-actions">
                <button class="button secondary" type="button" data-close-dialog>Cancelar</button>
                <button class="button danger" type="submit">Confirmar rechazo</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>

<?php if (! $esSolicitud && $puedeEliminar): ?>
    <dialog id="delete-dialog" aria-labelledby="delete-title">
        <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/eliminar') ?>" method="post">
            <?= csrf_field() ?>
            <h2 id="delete-title">Eliminar cotización</h2>
            <p>Se eliminará <?= esc($codigo) ?>. Esta acción no se puede deshacer y solo está disponible para cotizaciones sin contrato ni versiones posteriores.</p>
            <div class="dialog-actions">
                <button class="button secondary" type="button" data-close-dialog>Cancelar</button>
                <button class="button danger" type="submit">Eliminar definitivamente</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>

<script>
(() => {
    document.querySelectorAll('[data-close-dialog]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });
    const openers = [
        ['open-validity', 'validity-dialog'],
        ['open-accept', 'accept-dialog'],
        ['open-reject', 'reject-dialog'],
        ['open-delete', 'delete-dialog'],
    ];
    openers.forEach(([buttonId, dialogId]) => {
        const button = document.getElementById(buttonId);
        const dialog = document.getElementById(dialogId);
        if (button && dialog) button.addEventListener('click', () => dialog.showModal());
    });
    document.querySelectorAll('.quote-form form').forEach((form) => {
        form.addEventListener('submit', () => {
            if (form.dataset.submitting === 'true') return;
            form.dataset.submitting = 'true';
            form.classList.add('form-submitting');
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
                button.textContent = 'Guardando…';
            });
        });
    });
})();
</script>
<?= $this->endSection() ?>
