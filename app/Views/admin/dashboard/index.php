<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= ! empty($esReporte) ? 'Reportes' : 'Dashboard' ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php
$filtros = $filtrosTabla;
$urlTabla = base_url(! empty($esReporte) ? 'admin/reportes' : 'admin/dashboard');
$enlace = static function (array $cambios) use ($urlTabla, $filtros, $periodo, $desde, $hasta): string {
    $base = [
        'periodo' => $periodo,
        'desde' => $desde,
        'hasta' => $hasta,
        'tq' => $filtros['q'],
        'testado' => $filtros['estado'],
        'trevision' => $filtros['revision'],
        'ttipo' => $filtros['tipo'],
        'tdesde' => $filtros['desde'],
        'thasta' => $filtros['hasta'],
        'torden' => $filtros['orden'],
        'tdir' => $filtros['dir'],
    ];
    $base = array_filter($base, static fn ($valor): bool => $valor !== '' && $valor !== null);

    return $urlTabla . '?' . http_build_query(array_merge($base, $cambios));
};
$ordenar = static function (string $columna, string $etiqueta) use ($filtros, $enlace): string {
    $activo = $filtros['orden'] === $columna;
    $direccion = $activo && $filtros['dir'] === 'asc' ? 'desc' : 'asc';
    $flecha = $activo ? ($filtros['dir'] === 'asc' ? '▲' : '▼') : '↕';

    return '<a class="sort-link' . ($activo ? ' is-active' : '') . '" href="' . esc($enlace(['torden' => $columna, 'tdir' => $direccion, 'tpagina' => 1])) . '">' . esc($etiqueta) . ' <span aria-hidden="true">' . $flecha . '</span></a>';
};
?>
<div class="dashboard-page">
<div class="page-heading">
    <section class="dashboard-hero">
        <div class="dashboard-hero-copy">
            <span class="dashboard-eyebrow">PIERO PRODUCCIONES <i></i> GESTIÓN COMERCIAL</span>
            <h1><?= ! empty($esReporte) ? 'Reportes' : 'Dashboard' ?></h1>
            <p><?= ! empty($esReporte) ? 'Indicadores del periodo y evolución comercial.' : 'Tu estudio, en un solo lugar' ?></p>
        </div>
        <div class="dashboard-hero-image" role="img" aria-label="Equipo de producción audiovisual">
            <span>Producción audiovisual</span>
        </div>
    </section>
</div>

<section class="panel period-bar">
    <form action="<?= $urlTabla ?>" method="get">
        <div class="period-controls">
            <label class="filter-field">
                <span>Periodo del gráfico</span>
                <select name="periodo" id="periodo-select">
                    <?php foreach (['7d' => 'Últimos 7 días', '30d' => 'Últimos 30 días', 'mes' => 'Mes actual', 'mes_anterior' => 'Mes anterior', 'custom' => 'Rango personalizado'] as $valor => $etiqueta): ?>
                        <option value="<?= esc($valor) ?>" <?= $periodo === $valor ? 'selected' : '' ?>><?= esc($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-field custom-range" data-custom-range>
                <span>Desde</span>
                <input type="date" name="desde" value="<?= esc($desde) ?>">
            </label>
            <label class="filter-field custom-range" data-custom-range>
                <span>Hasta</span>
                <input type="date" name="hasta" value="<?= esc($hasta) ?>">
            </label>
            <div class="filter-actions">
                <button class="button primary compact" type="submit">Aplicar</button>
            </div>
        </div>
        <p class="period-caption"><?= esc($etiquetaPeriodo) ?> · <?= (int) $documentosPeriodo ?> documentos registrados.</p>
    </form>
</section>

<div class="dashboard-metrics">
    <article class="panel metric-card">
        <div class="metric-heading"><span>Documentos del periodo</span><i class="metric-icon icon-calendar" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg></i></div>
        <strong><?= number_format($documentosPeriodo) ?></strong>
        <small class="<?= $variacion >= 0 ? 'metric-positive' : 'metric-negative' ?>"><?= $variacion > 0 ? '+' : '' ?><?= number_format($variacion, 1) ?>% frente al periodo anterior</small>
    </article>
    <article class="panel metric-card">
        <div class="metric-heading"><span>Solicitudes por revisar</span><i class="metric-icon icon-pending" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></i></div>
        <strong><?= number_format($solicitudesPendientes) ?></strong>
        <small>Sin revisar o en revisión</small>
    </article>
    <article class="panel metric-card">
        <div class="metric-heading"><span>Cotizaciones vigentes</span><i class="metric-icon icon-approved" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg></i></div>
        <strong><?= number_format($cotizacionesVigentes) ?></strong>
        <small>Enviadas y dentro de vigencia</small>
    </article>
    <article class="panel metric-card">
        <div class="metric-heading"><span>Monto contratado</span><i class="metric-icon icon-income" aria-hidden="true">S/</i></div>
        <strong>S/ <?= number_format($montoContratado, 2) ?></strong>
        <small><?= (int) $contratosActivos ?> contrato(s) activo(s)</small>
    </article>
</div>

<div class="dashboard-charts">
    <section class="panel chart-panel chart-wide">
        <div class="chart-heading">
            <div><h2>Evolución de solicitudes y cotizaciones</h2><p><?= esc($etiquetaPeriodo) ?></p></div>
            <span class="chart-unit">Documentos</span>
        </div>
        <canvas id="quotes-month-chart" aria-label="Evolución de solicitudes y cotizaciones" role="img"></canvas>
        <div class="chart-legend">
            <span><i class="legend-solicitud"></i>Solicitudes</span>
            <span><i class="legend-cotizacion"></i>Cotizaciones formales</span>
        </div>
    </section>
    <section class="panel chart-panel">
        <div class="chart-heading"><div><h2>Estado comercial</h2><p>Distribución histórica</p></div></div>
        <canvas id="quotes-status-chart" aria-label="Distribución de estados comerciales" role="img"></canvas>
        <div class="chart-legend">
            <span><i class="legend-solicitud"></i>Solicitudes <strong><?= number_format($graficoEstados['solicitudes']) ?></strong></span>
            <span><i class="legend-borrador"></i>Borradores <strong><?= number_format($graficoEstados['borradores']) ?></strong></span>
            <span><i class="legend-approved"></i>Vigentes <strong><?= number_format($graficoEstados['enviadas']) ?></strong></span>
            <span><i class="legend-aceptada"></i>Aceptadas <strong><?= number_format($graficoEstados['aceptadas']) ?></strong></span>
            <span><i class="legend-rejected"></i>Rechazadas <strong><?= number_format($graficoEstados['rechazadas']) ?></strong></span>
        </div>
    </section>
    <?php if (! empty($esReporte)): ?>
    <section class="panel chart-panel chart-wide">
        <h2>Servicios más solicitados</h2>
        <?php if ($serviciosSolicitados === []): ?>
            <p>Aún no hay servicios en el periodo seleccionado.</p>
        <?php else: ?>
            <div class="service-ranking">
                <?php $maxServicios = max(array_column($serviciosSolicitados, 'total')); ?>
                <?php foreach ($serviciosSolicitados as $servicio): ?>
                    <div class="ranking-row">
                        <span><?= esc($servicio['nombre']) ?></span>
                        <div class="ranking-track"><i style="width: <?= esc((string) max(4, round(($servicio['total'] / $maxServicios) * 100))) ?>%"></i></div>
                        <strong><?= number_format($servicio['total']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<section class="panel dashboard-table-panel">
    <div class="panel-section-heading">
        <div>
            <h2>Últimas cotizaciones y solicitudes recibidas</h2>
            <p>Búsqueda, filtros combinables, orden y paginación del lado del servidor.</p>
        </div>
        <span class="chart-unit"><?= (int) $totalTabla ?> registros</span>
    </div>

    <form class="table-filters" action="<?= $urlTabla ?>" method="get">
        <input type="hidden" name="periodo" value="<?= esc($periodo) ?>">
        <input type="hidden" name="desde" value="<?= esc($desde) ?>">
        <input type="hidden" name="hasta" value="<?= esc($hasta) ?>">
        <label class="filter-field filter-search">
            <span>Buscar</span>
            <input type="search" name="tq" value="<?= esc($filtros['q']) ?>" placeholder="Código, institución, encargado o teléfono">
        </label>
        <label class="filter-field">
            <span>Tipo</span>
            <select name="ttipo">
                <option value="">Todos</option>
                <option value="solicitud" <?= $filtros['tipo'] === 'solicitud' ? 'selected' : '' ?>>Solicitudes</option>
                <option value="cotizacion" <?= $filtros['tipo'] === 'cotizacion' ? 'selected' : '' ?>>Cotizaciones</option>
                <option value="contrato" <?= $filtros['tipo'] === 'contrato' ? 'selected' : '' ?>>Con contrato</option>
            </select>
        </label>
        <label class="filter-field">
            <span>Estado comercial</span>
            <select name="testado">
                <?php foreach (['' => 'Todos', 'borrador' => 'Borrador', 'enviada' => 'Vigente', 'vencida' => 'Vencida', 'aceptada' => 'Aceptada', 'rechazada' => 'Rechazada'] as $valor => $etiqueta): ?>
                    <option value="<?= esc($valor) ?>" <?= $filtros['estado'] === $valor ? 'selected' : '' ?>><?= esc($etiqueta) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="filter-field">
            <span>Revisión</span>
            <select name="trevision">
                <?php foreach (['' => 'Todas', 'SIN_REVISAR' => 'Sin revisar', 'EN_REVISION' => 'En revisión', 'REVISADA' => 'Revisada'] as $valor => $etiqueta): ?>
                    <option value="<?= esc($valor) ?>" <?= $filtros['revision'] === $valor ? 'selected' : '' ?>><?= esc($etiqueta) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="filter-field">
            <span>Desde</span>
            <input type="date" name="tdesde" value="<?= esc($filtros['desde']) ?>">
        </label>
        <label class="filter-field">
            <span>Hasta</span>
            <input type="date" name="thasta" value="<?= esc($filtros['hasta']) ?>">
        </label>
        <div class="filter-actions">
            <button class="button primary compact" type="submit">Filtrar</button>
            <a class="button secondary compact" href="<?= esc($enlace(['tq' => '', 'ttipo' => '', 'testado' => '', 'trevision' => '', 'tdesde' => '', 'thasta' => '', 'torden' => 'fecha', 'tdir' => 'desc', 'tpagina' => 1])) ?>">Limpiar</a>
        </div>
    </form>

    <?php if ($tabla === []): ?>
        <p class="empty-state">No hay registros que coincidan con los filtros seleccionados.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table dashboard-table">
                <thead>
                    <tr>
                        <th><?= $ordenar('codigo', 'Código') ?></th>
                        <th><?= $ordenar('institucion', 'Institución') ?></th>
                        <th>Tipo</th>
                        <th><?= $ordenar('total', 'Total') ?></th>
                        <th>Estado</th>
                        <th>Revisión</th>
                        <th><?= $ordenar('fecha', 'Fecha') ?></th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tabla as $fila): ?>
                        <?php
                        $revision = (string) ($fila['estado_revision'] ?? 'SIN_REVISAR');
                        $tipoFila = ! empty($fila['contrato_id']) ? 'Contrato' : ($fila['es_formal'] ? 'Cotización' : 'Solicitud');
                        ?>
                        <tr>
                            <td><strong><?= esc($fila['codigo_visible']) ?></strong><?= $fila['es_formal'] ? '<br><small>Versión ' . (int) $fila['version'] . '</small>' : '' ?></td>
                            <td>
                                <strong><?= esc($fila['nombre_colegio']) ?></strong>
                                <br><small><?= esc($fila['persona_encargada'] ?: 'Sin encargado') ?><?= ! empty($fila['telefono_contacto']) ? ' · ' . esc($fila['telefono_contacto']) : '' ?></small>
                            </td>
                            <td><?= esc($tipoFila) ?></td>
                            <td>S/ <?= number_format((float) $fila['total_seccion'], 2) ?></td>
                            <td><span class="status status-<?= esc($fila['estado_efectivo']) ?>"><?= esc(\App\Models\CotizacionModel::etiquetaEstado($fila['estado_efectivo'])) ?></span></td>
                            <td><span class="status status-revision-<?= esc(strtolower($revision)) ?>"><?= esc(\App\Models\CotizacionModel::etiquetaRevision($revision)) ?></span></td>
                            <td><?= esc($fila['created_at'] ? date('d/m/Y H:i', strtotime($fila['created_at'])) : 'Sin fecha') ?></td>
                            <td>
                                <div class="icon-actions">
                                    <a class="icon-button" href="<?= base_url('admin/cotizaciones/' . $fila['id'] . '/ver') ?>" title="Ver detalle" aria-label="Ver detalle de <?= esc($fila['codigo_visible']) ?>">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                    </a>
                                    <?php if ($fila['es_formal'] && (float) $fila['total_seccion'] > 0): ?>
                                        <a class="icon-button" href="<?= base_url('admin/cotizaciones/' . $fila['id'] . '/pdf') ?>" title="Descargar PDF" aria-label="Descargar PDF de <?= esc($fila['codigo_visible']) ?>">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (! empty($fila['contrato_id'])): ?>
                                        <a class="icon-button" href="<?= base_url('admin/contratos/' . $fila['contrato_id']) ?>" title="Abrir contrato" aria-label="Abrir contrato de <?= esc($fila['codigo_visible']) ?>">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14v18H5zM8 8h8M8 12h8M8 16h5"/></svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPaginasTabla > 1): ?>
            <nav class="pagination" aria-label="Paginación del dashboard">
                <span>Mostrando <?= count($tabla) ?> de <?= (int) $totalTabla ?> registros</span>
                <div class="pagination-links">
                    <?php if ($paginaTabla > 1): ?><a class="button secondary compact" href="<?= esc($enlace(['tpagina' => $paginaTabla - 1])) ?>">Anterior</a><?php endif; ?>
                    <span>Página <?= (int) $paginaTabla ?> de <?= (int) $totalPaginasTabla ?></span>
                    <?php if ($paginaTabla < $totalPaginasTabla): ?><a class="button secondary compact" href="<?= esc($enlace(['tpagina' => $paginaTabla + 1])) ?>">Siguiente</a><?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<script>
(() => {
    const serie = <?= json_encode($grafico, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const estados = <?= json_encode($graficoEstados, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const colors = ['#ffc800', '#111111', '#62a673', '#2f7fb8', '#e77d72'];
    const draw = (canvas, render) => {
        if (!canvas) return;
        const ratio = window.devicePixelRatio || 1;
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        const context = canvas.getContext('2d');
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        render(context, width, height);
    };
    const renderLinea = () => draw(document.getElementById('quotes-month-chart'), (context, width, height) => {
        const left = 34;
        const right = 12;
        const top = 16;
        const bottom = height - 28;
        const ancho = Math.max(0, width - left - right);
        const alto = Math.max(0, bottom - top);
        const max = Math.max(1, ...serie.map((d) => Math.max(d.solicitudes, d.cotizaciones)));
        context.font = '11px Segoe UI, sans-serif';
        context.textAlign = 'right';
        context.textBaseline = 'middle';
        for (let tick = 0; tick <= 4; tick++) {
            const y = top + (alto / 4) * tick;
            context.beginPath();
            context.moveTo(left, y);
            context.lineTo(width - right, y);
            context.strokeStyle = '#edf0ef';
            context.stroke();
            context.fillStyle = '#89918f';
            context.fillText(String(Math.round(max * (1 - tick / 4))), left - 8, y);
        }
        const punto = (indice, valor) => ({
            x: left + (serie.length < 2 ? ancho / 2 : (indice / (serie.length - 1)) * ancho),
            y: bottom - (valor / max) * alto,
        });
        const series = [
            {clave: 'solicitudes', color: '#ffc800', relleno: 'rgba(255, 200, 0, .14)'},
            {clave: 'cotizaciones', color: '#111111', relleno: 'rgba(17, 17, 17, .06)'},
        ];
        series.forEach((s) => {
            const puntos = serie.map((d, i) => punto(i, Number(d[s.clave]) || 0));
            if (puntos.length === 0) return;
            context.beginPath();
            context.moveTo(puntos[0].x, bottom);
            puntos.forEach((p) => context.lineTo(p.x, p.y));
            context.lineTo(puntos[puntos.length - 1].x, bottom);
            context.closePath();
            context.fillStyle = s.relleno;
            context.fill();
            context.beginPath();
            puntos.forEach((p, i) => i === 0 ? context.moveTo(p.x, p.y) : context.lineTo(p.x, p.y));
            context.strokeStyle = s.color;
            context.lineWidth = 2.4;
            context.lineJoin = 'round';
            context.lineCap = 'round';
            context.stroke();
        });
        const paso = Math.max(1, Math.ceil(serie.length / 8));
        context.textAlign = 'center';
        context.textBaseline = 'alphabetic';
        serie.forEach((d, i) => {
            if (i % paso !== 0 && i !== serie.length - 1) return;
            const p = punto(i, 0);
            context.fillStyle = '#68716e';
            context.fillText(d.label, p.x, height - 8);
        });
    });
    const renderEstados = () => draw(document.getElementById('quotes-status-chart'), (context, width, height) => {
        const valores = [estados.solicitudes, estados.borradores, estados.enviadas, estados.aceptadas, estados.rechazadas].map(Number);
        const total = valores.reduce((suma, valor) => suma + valor, 0);
        const cx = width / 2;
        const cy = height * 0.47;
        const radio = Math.min(width * 0.3, height * 0.36);
        if (total === 0) {
            context.beginPath();
            context.arc(cx, cy, radio, 0, Math.PI * 2);
            context.strokeStyle = '#e2e2da';
            context.lineWidth = 18;
            context.stroke();
        } else {
            let inicio = -Math.PI / 2;
            valores.forEach((valor, indice) => {
                if (valor === 0) return;
                const fin = inicio + (valor / total) * Math.PI * 2;
                context.beginPath();
                context.arc(cx, cy, radio, inicio, fin);
                context.strokeStyle = colors[indice];
                context.lineWidth = 18;
                context.lineCap = 'butt';
                context.stroke();
                inicio = fin;
            });
        }
        context.fillStyle = '#202926';
        context.font = '700 24px Segoe UI, sans-serif';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillText(String(total), cx, cy - 4);
        context.fillStyle = '#89918f';
        context.font = '10px Segoe UI, sans-serif';
        context.fillText('DOCUMENTOS', cx, cy + 15);
    });
    const redibujar = () => { renderLinea(); renderEstados(); };
    redibujar();
    window.addEventListener('resize', redibujar);
    const selector = document.getElementById('periodo-select');
    const alternarRango = () => {
        document.querySelectorAll('[data-custom-range]').forEach((campo) => {
            campo.style.display = selector && selector.value === 'custom' ? '' : 'none';
        });
    };
    selector?.addEventListener('change', alternarRango);
    alternarRango();
})();
</script>
</div>
<?= $this->endSection() ?>
