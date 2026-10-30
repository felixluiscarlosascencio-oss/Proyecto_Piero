<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Nueva cotización<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <a class="back-link" href="<?= base_url('admin/cotizaciones') ?>">&larr; Volver a cotizaciones</a>
        <h1>Crear cotización</h1>
    </div>
</div>

<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>

<form class="panel quote-form" action="<?= base_url('admin/cotizaciones/crear') ?>" method="post">
    <?= csrf_field() ?>
    <div class="form-grid">
        <label class="form-field full">Colegio o institución
            <input name="nombre_colegio" maxlength="150" value="<?= esc(old('nombre_colegio')) ?>" required>
        </label>
                <label class="form-field full">Secciones de los estudiantes
            <input name="nombre_colegio" maxlength="10" value="<?= esc(old('nombre_colegio')) ?>" required>
        </label>
        <label class="form-field">Cantidad de estudiantes
            <input name="cantidad_estudiantes" type="number" min="1" max="10000" value="<?= esc(old('cantidad_estudiantes', 1)) ?>" required>
        </label>
    </div>
    <h2>Servicios</h2>
    <div id="service-list">
        <?php $serviciosAnteriores = old('servicios'); ?>
        <?php if (! is_array($serviciosAnteriores) || $serviciosAnteriores === []): ?>
            <?php $serviciosAnteriores = [['nombre' => '', 'precio_unitario' => 0]]; ?>
        <?php endif; ?>
        <?php foreach ($serviciosAnteriores as $indice => $servicio): ?>
            <div class="service-row">
                <label>Servicio
                    <input name="servicios[<?= esc($indice) ?>][nombre]" maxlength="120" value="<?= esc($servicio['nombre'] ?? '') ?>" required>
                </label>
                <label>Precio por estudiante (S/)
                    <input name="servicios[<?= esc($indice) ?>][precio_unitario]" type="number" min="0" step="0.01" value="<?= esc($servicio['precio_unitario'] ?? 0) ?>" required>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="button secondary" id="add-service" type="button">Agregar servicio</button>
    <div class="action-row">
        <button class="button primary" type="submit">Crear cotización</button>
    </div>
</form>

<script>
    const serviceList = document.getElementById('service-list');
    document.getElementById('add-service').addEventListener('click', () => {
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
</script>
<?= $this->endSection() ?>