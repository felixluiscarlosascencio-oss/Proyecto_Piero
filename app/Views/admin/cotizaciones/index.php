<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= ! empty($vigentes) ? 'Cotizaciones vigentes' : 'Cotizaciones' ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php
$urlBase = ! empty($vigentes) ? 'admin/cotizaciones/vigentes' : 'admin/cotizaciones';
$puedeEliminar = \App\Libraries\AdminAccess::can('cotizaciones.eliminar');
$estados = [
    '' => 'Todos los estados',
    'sin_revisar' => 'Solicitudes sin revisar',
    'en_revision' => 'Solicitudes en revisión',
    'revisadas' => 'Revisadas',
    'borradores' => 'Cotizaciones en borrador',
    'vigentes' => 'Cotizaciones vigentes',
    'vencidas' => 'Cotizaciones vencidas',
    'aceptadas' => 'Aceptadas / convertidas',
    'rechazadas' => 'Rechazadas',
];
?>
<div class="page-heading">
    <div>
        <h1><?= ! empty($vigentes) ? 'Cotizaciones vigentes' : 'Cotizaciones' ?></h1>
        <p><?= ! empty($vigentes) ? 'Propuestas enviadas y dentro de su periodo de vigencia.' : 'Solicitudes, cotizaciones formales y su estado comercial.' ?></p>
    </div>
    <?php if (! empty($puedeCrear) && empty($vigentes)): ?>
        <a class="button primary" href="<?= base_url('admin/cotizaciones/nueva') ?>">Crear cotización</a>
    <?php endif; ?>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?>
    <div class="alert success"><?= esc($mensaje) ?></div>
<?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?>
    <div class="alert error"><?= esc($error) ?></div>
<?php endif; ?>

<form class="panel filter-bar" action="<?= base_url($urlBase) ?>" method="get" role="search">
    <label class="filter-field filter-search">
        <span>Buscar</span>
        <input type="search" name="q" value="<?= esc($texto) ?>" placeholder="Código, institución, encargado o teléfono">
    </label>
    <label class="filter-field">
        <span>Estado</span>
        <select name="estado">
            <?php foreach ($estados as $valor => $etiqueta): ?>
                <option value="<?= esc($valor) ?>" <?= $filtroEstado === $valor ? 'selected' : '' ?>><?= esc($etiqueta) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="filter-actions">
        <button class="button primary compact" type="submit">Filtrar</button>
        <a class="button secondary compact" href="<?= base_url($urlBase) ?>">Limpiar</a>
    </div>
</form>

<?php if ($cotizaciones === []): ?>
    <section class="panel empty-state">No hay registros que coincidan con los filtros.</section>
<?php else: ?>
    <div class="quote-list">
        <?php foreach ($cotizaciones as $cotizacion): ?>
            <?php
            $servicios = $cotizacion['servicios'];
            $revision = (string) ($cotizacion['estado_revision'] ?? 'SIN_REVISAR');
            $esFormal = ! empty($cotizacion['es_formal']);
            ?>
            <article class="panel quote-card">
                <div class="quote-heading">
                    <div>
                        <span class="quote-id"><?= esc($cotizacion['codigo_visible']) ?><?= $esFormal ? ' · Versión ' . (int) $cotizacion['version'] : '' ?></span>
                        <h2><?= esc($cotizacion['nombre_colegio']) ?></h2>
                        <p class="quote-subtitle"><?= $esFormal ? 'Cotización formal' : 'Solicitud recibida' ?> · <?= esc($cotizacion['created_at'] ? date('d/m/Y H:i', strtotime($cotizacion['created_at'])) : 'Sin fecha') ?></p>
                    </div>
                    <div class="heading-badges">
                        <span class="status status-<?= esc($cotizacion['estado_efectivo']) ?>"><?= esc(\App\Models\CotizacionModel::etiquetaEstado($cotizacion['estado_efectivo'])) ?></span>
                        <span class="status status-revision-<?= esc(strtolower($revision)) ?>"><?= esc(\App\Models\CotizacionModel::etiquetaRevision($revision)) ?></span>
                    </div>
                </div>
                <div class="quote-meta-grid">
                    <p><strong>Estudiantes:</strong> <?= esc($cotizacion['cantidad_estudiantes']) ?></p>
                    <p><strong>Promoción / sección:</strong> <?= esc($cotizacion['nombre_seccion'] ?: 'No especificada') ?></p>
                    <p><strong>Encargado/a:</strong> <?= esc($cotizacion['persona_encargada'] ?: 'Sin dato') ?></p>
                    <p><strong>Teléfono:</strong> <?= esc($cotizacion['telefono_contacto'] ?: 'Sin dato') ?></p>
                    <p><strong>Total:</strong> S/ <?= number_format((float) $cotizacion['total_seccion'], 2) ?></p>
                    <p><strong>Vigente hasta:</strong> <?= ! empty($cotizacion['vence_en']) ? esc(date('d/m/Y', strtotime($cotizacion['vence_en']))) : 'Sin definir' ?></p>
                </div>
                <p class="quote-services-line"><strong>Servicios:</strong> <?= esc(implode(', ', array_column($servicios, 'nombre')) ?: 'Sin servicios') ?></p>
                <div class="action-row">
                    <a class="button secondary compact" href="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/ver') ?>">Abrir detalle</a>
                    <?php if (! empty($cotizacion['contrato_id'])): ?>
                        <a class="button secondary compact" href="<?= base_url('admin/contratos/' . $cotizacion['contrato_id']) ?>">Contrato <?= esc($cotizacion['contrato_codigo'] ?: '#' . $cotizacion['contrato_id']) ?></a>
                    <?php endif; ?>
                    <?php if ($esFormal && (float) $cotizacion['total_seccion'] > 0): ?>
                        <a class="icon-button" href="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/pdf') ?>" title="Descargar PDF" aria-label="Descargar PDF">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($puedeEliminar && empty($cotizacion['contrato_id']) && $cotizacion['cotizacion_origen_id'] === null && $cotizacion['version'] == 1 && $cotizacion['estado'] === 'borrador'): ?>
                        <form action="<?= base_url('admin/cotizaciones/' . $cotizacion['id'] . '/eliminar') ?>" method="post" onsubmit="return confirm('¿Eliminar <?= esc($cotizacion['codigo_visible']) ?>? Esta acción no se puede deshacer.');">
                            <?= csrf_field() ?>
                            <button class="icon-button danger" type="submit" title="Eliminar" aria-label="Eliminar <?= esc($cotizacion['codigo_visible']) ?>">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 14h8l1-14M10 11v6M14 11v6"/></svg>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (($totalPaginas ?? 1) > 1): ?>
    <?php
    $queryBase = array_filter(['q' => $texto, 'estado' => $filtroEstado], static fn ($valor): bool => $valor !== '' && $valor !== null);
    $enlacePagina = static function (int $numero) use ($urlBase, $queryBase): string {
        return base_url($urlBase) . '?' . http_build_query(array_merge($queryBase, ['pagina' => $numero]));
    };
    ?>
    <nav class="pagination" aria-label="Paginación de cotizaciones">
        <span>Mostrando <?= count($cotizaciones) ?> de <?= (int) $total ?> registros</span>
        <div class="pagination-links">
            <?php if ($pagina > 1): ?><a class="button secondary compact" href="<?= esc($enlacePagina($pagina - 1)) ?>">Anterior</a><?php endif; ?>
            <span>Página <?= (int) $pagina ?> de <?= (int) $totalPaginas ?></span>
            <?php if ($pagina < $totalPaginas): ?><a class="button secondary compact" href="<?= esc($enlacePagina($pagina + 1)) ?>">Siguiente</a><?php endif; ?>
        </div>
    </nav>
<?php endif; ?>
<?= $this->endSection() ?>
