<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Nueva cotización<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <a class="back-link" href="<?= base_url('admin/cotizaciones') ?>">&larr; Volver a cotizaciones</a>
        <h1><?= $duplicada !== null ? 'Duplicar cotización #' . esc($duplicada['id']) : 'Crear cotización' ?></h1>
    </div>
</div>

<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>
<?php if ($duplicada !== null): ?><div class="alert success">Se creará una propuesta independiente basada en la cotización original. Sus servicios y precios se conservarán como una nueva copia.</div><?php endif; ?>

<form class="panel quote-form" action="<?= base_url('admin/cotizaciones/crear') ?>" method="post">
    <?= csrf_field() ?>
    <?php if ($duplicada !== null): ?><input type="hidden" name="duplicar_de" value="<?= esc($duplicada['id']) ?>"><?php endif; ?>
    <div class="form-grid">
        <label class="form-field full">Nombre de la institución
            <input name="nombre_colegio" maxlength="150" value="<?= esc(old('nombre_colegio', $duplicada['nombre_colegio'] ?? '')) ?>" required>
        </label>
        <label class="form-field full">Nombre de la promoción o sección <span>(opcional)</span>
            <input name="nombre_seccion" maxlength="120" value="<?= esc(old('nombre_seccion', $duplicada['nombre_seccion'] ?? '')) ?>">
        </label>
        <label class="form-field">Cantidad de alumnos
            <input id="cantidad-estudiantes" name="cantidad_estudiantes" type="number" min="1" max="10000" value="<?= esc(old('cantidad_estudiantes', $duplicada['cantidad_estudiantes'] ?? 1)) ?>" required>
        </label>
        <label class="form-field">Persona encargada o tesorera
            <input name="persona_encargada" maxlength="150" value="<?= esc(old('persona_encargada', $duplicada['persona_encargada'] ?? '')) ?>" required>
        </label>
        <label class="form-field">Teléfono / WhatsApp de contacto
            <input name="telefono_contacto" type="tel" maxlength="30" value="<?= esc(old('telefono_contacto', $duplicada['telefono_contacto'] ?? '')) ?>" required>
        </label>
    </div>
    <h2>Selección de servicios</h2>
    <?php $serviciosAnteriores = old('servicios', []); ?>
    <?php $serviciosCatalogoConPrecio = array_filter($serviciosCatalogo, static fn ($servicio): bool => (float) $servicio['precio_interno'] > 0); ?>
    <?php $hayServicios = $duplicada !== null ? $duplicada['servicios'] !== [] : $serviciosCatalogoConPrecio !== []; ?>
    <?php if (! $hayServicios && $duplicada !== null): ?>
        <p>La cotización original no contiene servicios válidos para duplicar.</p>
    <?php elseif (! $hayServicios && $serviciosCatalogo === []): ?>
        <p>No hay servicios activos en el catálogo. Configura el catálogo antes de crear cotizaciones.</p>
    <?php elseif (! $hayServicios): ?>
        <p>Configura un precio interno mayor que cero en el catálogo para los servicios antes de cotizar.</p>
    <?php elseif ($duplicada !== null): ?>
        <div class="service-selection">
            <?php foreach ($duplicada['servicios'] as $indice => $servicio): ?>
                <div class="service-selection-row">
                    <label><input class="service-checkbox" type="checkbox" value="<?= esc((string) ($servicio['servicio_id'] ?? $indice)) ?>" checked disabled> <?= esc($servicio['nombre'] ?? '') ?></label>
                    <span>Precio copiado: S/ <?= number_format((float) ($servicio['precio_unitario'] ?? 0), 2) ?> por alumno</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="service-selection">
            <?php foreach ($serviciosCatalogoConPrecio as $servicio): ?>
                <?php
                $idsSeleccionados = is_array($serviciosAnteriores)
                    ? array_map(static fn ($value): string => is_scalar($value) ? (string) $value : '', $serviciosAnteriores)
                    : [];
                ?>
                <div class="service-selection-row">
                    <label><input class="service-checkbox" type="checkbox" name="servicios[]" value="<?= esc($servicio['id']) ?>" <?= in_array((string) $servicio['id'], $idsSeleccionados, true) ? 'checked' : '' ?>> <?= esc($servicio['nombre_item']) ?></label>
                    <label class="service-price-field">Precio de cotización por alumno (S/)
                        <input class="service-price" name="precios[<?= esc($servicio['id']) ?>]" type="number" min="0.01" max="<?= esc($servicio['precio_interno']) ?>" step="0.01" value="<?= esc(old('precios.' . $servicio['id'], $servicio['precio_interno'])) ?>">
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p class="quote-estimate" aria-live="polite">Total estimado: S/ <strong id="total-estimado">0.00</strong></p>
    <div class="action-row">
        <button class="button primary" type="submit" <?= ! $hayServicios ? 'disabled' : '' ?>>Crear cotización</button>
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

        const totalFijo = <?= $duplicada !== null ? json_encode(array_sum(array_map(static fn ($servicio): float => (float) ($servicio['precio_unitario'] ?? 0), $duplicada['servicios']))) : 'null' ?>;
        const precioEstimado = totalFijo === null ? precioTotal : totalFijo;
        totalOutput.textContent = (Math.round(precioEstimado * cantidad * 100) / 100).toFixed(2);
    };

    form.addEventListener('input', actualizarTotal);
    form.addEventListener('change', actualizarTotal);
    actualizarTotal();
})();
</script>
<?= $this->endSection() ?>