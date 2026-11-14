<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= ! empty($esReporte) ? 'Reportes' : 'Dashboard' ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="dashboard-page">
<div class="page-heading">
    <section class="dashboard-hero">
        <div class="dashboard-hero-copy">
            <span class="dashboard-eyebrow">PIERO PRODUCCIONES <i></i> GESTIÓN COMERCIAL</span>
            <h1><?= ! empty($esReporte) ? 'Reportes' : 'Dashboard' ?></h1>
            <p><?= ! empty($esReporte) ? 'Indicadores de rendimiento de cotizaciones y servicios.' : 'Tu estudio, en un solo lugar' ?></p>
        </div>
        <div class="dashboard-hero-image" role="img" aria-label="Equipo de producción audiovisual">
            <span>Producción audiovisual</span>
        </div>
    </section>
</div>
<div class="dashboard-metrics">
    <article class="panel metric-card">
        <div class="metric-heading"><span>Cotizaciones del mes</span><i class="metric-icon icon-calendar" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg></i></div>
        <strong><?= number_format($cotizacionesMes) ?></strong>
        <small class="<?= $variacion >= 0 ? 'metric-positive' : 'metric-negative' ?>"><?= $variacion > 0 ? '+' : '' ?><?= number_format($variacion, 1) ?>% frente al mes anterior</small>
    </article>
    <article class="panel metric-card">
        <div class="metric-heading"><span>Pendientes</span><i class="metric-icon icon-pending" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></i></div>
        <strong><?= number_format($pendientes) ?></strong>
        <small>Requieren atención comercial</small>
    </article>
    <article class="panel metric-card">
        <div class="metric-heading"><span>Aprobadas</span><i class="metric-icon icon-approved" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg></i></div>
        <strong><?= number_format($aprobadas) ?></strong>
        <small>Convertidas a venta</small>
    </article>
    <article class="panel metric-card">
        <div class="metric-heading"><span>Ingresos estimados</span><i class="metric-icon icon-income" aria-hidden="true">S/</i></div>
        <strong>S/ <?= number_format($ingresosEstimados, 2) ?></strong>
        <small>En cotizaciones vigentes</small>
    </article>
</div>

<div class="dashboard-charts">
    <section class="panel chart-panel">
        <div class="chart-heading"><div><h2>Cotizaciones por mes</h2><p>Actividad de los últimos 6 meses</p></div><span class="chart-unit">Solicitudes</span></div>
        <canvas id="quotes-month-chart" aria-label="Cotizaciones por mes" role="img"></canvas>
    </section>
    <section class="panel chart-panel">
        <div class="chart-heading"><div><h2>Estado de cotizaciones</h2><p>Distribución por estado actual</p></div></div>
        <canvas id="quotes-status-chart" aria-label="Distribución de estados de cotizaciones" role="img"></canvas>
        <div class="chart-legend">
            <span><i class="legend-pending"></i>Pendientes <strong><?= number_format($graficoEstados['pendientes']) ?></strong></span>
            <span><i class="legend-approved"></i>Aprobadas <strong><?= number_format($graficoEstados['aprobadas']) ?></strong></span>
            <span><i class="legend-rejected"></i>Rechazadas <strong><?= number_format($graficoEstados['rechazadas']) ?></strong></span>
        </div>
    </section>
    <?php if (! empty($esReporte)): ?>
    <section class="panel chart-panel chart-wide">
        <h2>Servicios más solicitados</h2>
        <?php if ($serviciosSolicitados === []): ?>
            <p>Aún no hay servicios en las cotizaciones.</p>
        <?php else: ?>
            <div class="service-ranking">
                <?php $maxServicios = max($serviciosSolicitados); ?>
                <?php foreach ($serviciosSolicitados as $nombre => $solicitudes): ?>
                    <div class="ranking-row">
                        <span><?= esc($nombre) ?></span>
                        <div class="ranking-track"><i style="width: <?= esc((string) max(4, round(($solicitudes / $maxServicios) * 100))) ?>%"></i></div>
                        <strong><?= number_format($solicitudes) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>
<script>
(() => {
    const months = <?= json_encode($graficoMeses, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const states = <?= json_encode($graficoEstados, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const colors = ['#f6bd16', '#62a673', '#e77d72'];
    const draw = (canvas, render) => {
        if (!canvas) return;
        const ratio = window.devicePixelRatio || 1;
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        const context = canvas.getContext('2d');
        context.scale(ratio, ratio);
        render(context, width, height);
    };
    const renderMonths = () => draw(document.getElementById('quotes-month-chart'), (context, width, height) => {
        const values = months.map((month) => Number(month.total) || 0);
        const max = Math.max(1, ...values);
        const left = 32;
        const right = 12;
        const top = 18;
        const bottom = height - 30;
        const chartWidth = Math.max(0, width - left - right);
        const chartHeight = Math.max(0, bottom - top);
        const points = values.map((value, index) => ({
            x: left + (values.length < 2 ? chartWidth / 2 : (index / (values.length - 1)) * chartWidth),
            y: bottom - (value / max) * chartHeight,
        }));
        context.font = '11px Segoe UI, sans-serif';
        context.textAlign = 'right';
        context.textBaseline = 'middle';
        for (let tick = 0; tick <= 3; tick++) {
            const y = top + (chartHeight / 3) * tick;
            context.beginPath();
            context.moveTo(left, y);
            context.lineTo(width - right, y);
            context.strokeStyle = '#edf0ef';
            context.lineWidth = 1;
            context.stroke();
            context.fillStyle = '#89918f';
            context.fillText(String(Math.round(max * (1 - tick / 3))), left - 9, y);
        }
        if (points.length > 0) {
            const fill = context.createLinearGradient(0, top, 0, bottom);
            fill.addColorStop(0, 'rgba(246, 189, 22, .24)');
            fill.addColorStop(1, 'rgba(246, 189, 22, 0)');
            context.beginPath();
            context.moveTo(points[0].x, bottom);
            points.forEach((point) => context.lineTo(point.x, point.y));
            context.lineTo(points[points.length - 1].x, bottom);
            context.closePath();
            context.fillStyle = fill;
            context.fill();

            context.beginPath();
            points.forEach((point, index) => {
                if (index === 0) context.moveTo(point.x, point.y);
                else context.lineTo(point.x, point.y);
            });
            context.strokeStyle = '#e9b400';
            context.lineWidth = 2.5;
            context.lineJoin = 'round';
            context.lineCap = 'round';
            context.stroke();
        }
        context.textAlign = 'center';
        context.textBaseline = 'alphabetic';
        points.forEach((point, index) => {
            context.beginPath();
            context.arc(point.x, point.y, 4, 0, Math.PI * 2);
            context.fillStyle = '#fff';
            context.fill();
            context.strokeStyle = '#e9b400';
            context.lineWidth = 2.5;
            context.stroke();
            context.fillStyle = '#68716e';
            context.fillText(months[index].label.slice(0, 2), point.x, height - 5);
        });
    });
    const renderStates = () => draw(document.getElementById('quotes-status-chart'), (context, width, height) => {
        const values = [states.pendientes, states.aprobadas, states.rechazadas].map((value) => Number(value) || 0);
        const total = values.reduce((sum, value) => sum + value, 0);
        const cx = width / 2;
        const cy = height * 0.48;
        const radius = Math.min(width * 0.27, height * 0.37);
        if (total === 0) {
            context.beginPath();
            context.arc(cx, cy, radius, 0, Math.PI * 2);
            context.strokeStyle = '#e2e2da';
            context.lineWidth = 20;
            context.stroke();
        } else {
            let start = -Math.PI / 2;
            values.forEach((value, index) => {
                if (value === 0) return;
                const end = start + (value / total) * Math.PI * 2;
                context.beginPath();
                context.arc(cx, cy, radius, start, end);
                context.strokeStyle = colors[index];
                context.lineWidth = 20;
                context.lineCap = 'round';
                context.stroke();
                start = end;
            });
        }
        context.fillStyle = '#202926';
        context.font = '700 25px Segoe UI, sans-serif';
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.fillText(String(total), cx, cy - 5);
        context.fillStyle = '#89918f';
        context.font = '10px Segoe UI, sans-serif';
        context.fillText('COTIZACIONES', cx, cy + 14);
    });
    renderMonths();
    renderStates();
    window.addEventListener('resize', () => { renderMonths(); renderStates(); });
})();
</script>
</div>
<?= $this->endSection() ?>
