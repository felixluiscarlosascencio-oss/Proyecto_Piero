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
        <label class="form-field full">Nombre de la institución
            <input name="nombre_colegio" maxlength="150" value="<?= esc(old('nombre_colegio')) ?>" required>
        </label>
        <label class="form-field full">Nombre de la promoción o sección <span>(opcional)</span>
            <input name="nombre_seccion" maxlength="120" value="<?= esc(old('nombre_seccion')) ?>">
        </label>
        <label class="form-field">Cantidad de alumnos
            <input id="cantidad-estudiantes" name="cantidad_estudiantes" type="number" min="1" max="10000" value="<?= esc(old('cantidad_estudiantes', 1)) ?>" required>
        </label>
        <label class="form-field">Persona encargada o tesorera
            <input name="persona_encargada" maxlength="150" value="<?= esc(old('persona_encargada')) ?>" required>
        </label>
        <label class="form-field">Teléfono / WhatsApp de contacto
            <input name="telefono_contacto" type="tel" maxlength="30" value="<?= esc(old('telefono_contacto')) ?>" required>
        </label>
    </div>
    <h2>Selección de servicios</h2>
    <?php $serviciosAnteriores = old('servicios', []); ?>
    <?php if ($serviciosCatalogo === []): ?>
        <p>No hay servicios activos en el catálogo. Configura el catálogo antes de crear cotizaciones.</p>
    <?php else: ?>
        <div class="service-selection">
            <?php foreach ($serviciosCatalogo as $servicio): ?>
                <?php
                $idsSeleccionados = is_array($serviciosAnteriores)
                    ? array_map(static fn ($value): string => is_scalar($value) ? (string) $value : '', $serviciosAnteriores)
                    : [];
                ?>
                <label class="service-selection-row">
                    <input class="service-checkbox" type="checkbox" name="servicios[]" value="<?= esc($servicio['id']) ?>" <?= in_array((string) $servicio['id'], $idsSeleccionados, true) ? 'checked' : '' ?>>
                    <span><?= esc($servicio['nombre_item']) ?></span>
                    <label class="service-price-field">Precio de cotización por alumno (S/)
                        <input class="service-price" name="precios[<?= esc($servicio['id']) ?>]" type="number" min="0" max="9999999999.99" step="0.01" value="<?= esc(old('precios.' . $servicio['id'], $servicio['precio_interno'])) ?>">
                    </label>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p class="quote-estimate" aria-live="polite">Total estimado: S/ <strong id="total-estimado">0.00</strong></p>
    <div class="action-row">
        <button class="button primary" type="submit" <?= $serviciosCatalogo === [] ? 'disabled' : '' ?>>Crear cotización</button>
    </div>
</form>
<script>
(() => {
    const form = document.querySelector('.quote-form');
    const cantidadInput = document.getElementById('cantidad-estudiantes');
    const totalOutput = document.getElementById('total-estimado');

    if (!form || !cantidadInput || !totalOutput) {
        return;
    }

    const actualizarTotal = () => {
        const cantidad = Math.max(0, Number(cantidadInput.value) || 0);
        const precioTotal = Array.from(form.querySelectorAll('.service-checkbox:checked'))
            .reduce((total, checkbox) => {
                const precioInput = form.querySelector(`.service-price[name="precios[${checkbox.value}]"]`);
                return total + (precioInput ? Math.max(0, Number(precioInput.value) || 0) : 0);
            }, 0);

        totalOutput.textContent = (Math.round(precioTotal * cantidad * 100) / 100).toFixed(2);
    };

    form.addEventListener('input', actualizarTotal);
    form.addEventListener('change', actualizarTotal);
    actualizarTotal();
})();
</script>
<?= $this->endSection() ?>