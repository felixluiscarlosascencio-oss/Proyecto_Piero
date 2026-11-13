<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= ! empty($esReporte) ? 'Reportes' : 'Dashboard' ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <h1><?= ! empty($esReporte) ? 'Reportes' : 'Dashboard' ?></h1>
        <p><?= ! empty($esReporte) ? 'Indicadores de rendimiento de cotizaciones y servicios.' : 'Resumen operativo actualizado con la actividad de cotizaciones y servicios.' ?></p>
    </div>
</div>
<div class="dashboard-metrics">
    <article class="panel metric-card">
        <span>Cotizaciones del mes</span>
        <strong><?= number_format($cotizacionesMes) ?></strong>
        <small class="<?= $variacion >= 0 ? 'metric-positive' : 'metric-negative' ?>"><?= $variacion > 0 ? '+' : '' ?><?= number_format($variacion, 1) ?>% frente al mes anterior</small>
    </article>
    <article class="panel metric-card">
        <span>Pendientes</span>
        <strong><?= number_format($pendientes) ?></strong>
        <small>Requieren atención comercial</small>
    </article>
    <article class="panel metric-card">
        <span>Aprobadas</span>
        <strong><?= number_format($aprobadas) ?></strong>
        <small>Convertidas a venta</small>
    </article>
    <article class="panel metric-card">
        <span>Ingresos estimados</span>
        <strong>S/ <?= number_format($ingresosEstimados, 2) ?></strong>
        <small>En cotizaciones vigentes</small>
    </article>
</div>

<div class="dashboard-charts">
    <section class="panel chart-panel">
        <h2>Cotizaciones por mes</h2>
        <canvas id="quotes-month-chart" aria-label="Cotizaciones por mes" role="img"></canvas>
    </section>
    <section class="panel chart-panel">
        <h2>Estado de cotizaciones</h2>
        <canvas id="quotes-status-chart" aria-label="Distribución de estados de cotizaciones" role="img"></canvas>
        <div class="chart-legend">
            <span>Pendientes: <?= number_format($graficoEstados['pendientes']) ?></span>
            <span>Aprobadas: <?= number_format($graficoEstados['aprobadas']) ?></span>
            <span>Rechazadas: <?= number_format($graficoEstados['rechazadas']) ?></span>
        </div>
    </section>
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
</div>
<script>
(() => {
    const months = <?= json_encode($graficoMeses, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const states = <?= json_encode($graficoEstados, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const colors = ['#ffc800', '#14602e', '#b52b2b'];
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
        const gap = 14;
        const slot = width / Math.max(1, values.length);
        const barWidth = Math.min(42, slot - gap);
        context.font = '12px Segoe UI, sans-serif';
        context.textAlign = 'center';
        months.forEach((month, index) => {
            const barHeight = (values[index] / max) * (height - 38);
            const x = index * slot + (slot - barWidth) / 2;
            context.fillStyle = '#ffc800';
            context.fillRect(x, height - 24 - barHeight, barWidth, barHeight);
            context.fillStyle = '#5b5d56';
            context.fillText(month.label, x + barWidth / 2, height - 6);
            context.fillStyle = '#141414';
            context.fillText(String(values[index]), x + barWidth / 2, height - 30 - barHeight);
        });
    });
    const renderStates = () => draw(document.getElementById('quotes-status-chart'), (context, width, height) => {
        const values = [states.pendientes, states.aprobadas, states.rechazadas].map((value) => Number(value) || 0);
        const total = values.reduce((sum, value) => sum + value, 0);
        const cx = width / 2;
        const cy = height / 2;
        const radius = Math.min(width, height) * 0.36;
        if (total === 0) {
            context.beginPath();
            context.arc(cx, cy, radius, 0, Math.PI * 2);
            context.strokeStyle = '#e2e2da';
            context.lineWidth = 24;
            context.stroke();
            return;
        }
        let start = -Math.PI / 2;
        values.forEach((value, index) => {
            const end = start + (value / total) * Math.PI * 2;
            context.beginPath();
            context.arc(cx, cy, radius, start, end);
            context.strokeStyle = colors[index];
            context.lineWidth = 24;
            context.stroke();
            start = end;
        });
    });
    renderMonths();
    renderStates();
    window.addEventListener('resize', () => { renderMonths(); renderStates(); });
})();
</script>
<?= $this->endSection() ?>
