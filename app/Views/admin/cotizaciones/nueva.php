<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= $duplicada !== null ? 'Duplicar cotización #' . esc($duplicada['id']) : 'Nueva cotización' ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php
$error = session()->getFlashdata('error');
$urlCerrar = $duplicada !== null
    ? base_url('admin/cotizaciones/' . $duplicada['id'] . '/ver')
    : base_url('admin/cotizaciones');
?>
<div class="modal show quote-modal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="quote-modal-title" style="display:block">
    <div class="modal-dialog modal-dialog-centered quote-modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="quote-modal-title"><?= $duplicada !== null ? 'Duplicar cotización #' . esc($duplicada['id']) : 'Crear cotización' ?></h1>
                <a class="btn-close" href="<?= $urlCerrar ?>" aria-label="Cerrar"></a>
            </div>
<form class="quote-form quote-create-form" action="<?= base_url('admin/cotizaciones/crear') ?>" method="post">
    <?= csrf_field() ?>
    <?php if ($duplicada !== null): ?><input type="hidden" name="duplicar_de" value="<?= esc($duplicada['id']) ?>"><?php endif; ?>
    <div class="modal-body">
        <?php if ($error): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>
        <?php if ($duplicada !== null): ?><div class="alert success">Se creará una nueva versión vinculada a la cotización original. Los servicios existentes conservarán sus precios copiados y podrás agregar servicios del catálogo.</div><?php endif; ?>
    <div class="form-grid">
        <label class="form-field">Nombre de la institución
            <input name="nombre_colegio" maxlength="150" value="<?= esc(old('nombre_colegio', $duplicada['nombre_colegio'] ?? '')) ?>" required>
        </label>
        <label class="form-field">Nombre de la promoción o sección <span>(opcional)</span>
            <input name="nombre_seccion" maxlength="120" value="<?= esc(old('nombre_seccion', $duplicada['nombre_seccion'] ?? '')) ?>">
        </label>
        <label class="form-field">Cantidad de alumnos
            <input id="cantidad-estudiantes" name="cantidad_estudiantes" type="number" min="1" max="999" maxlength="3" step="1" oninput="this.value=this.value.replace(/[^0-9]/g, '').slice(0,3)" onfocus="this.select()" value="<?= esc(old('cantidad_estudiantes', $duplicada['cantidad_estudiantes'] ?? 1)) ?>" required>
        </label>
        <label class="form-field">Persona encargada o tesorera
            <input name="persona_encargada" maxlength="150" value="<?= esc(old('persona_encargada', $duplicada['persona_encargada'] ?? '')) ?>" required>
        </label>
        <label class="form-field">Teléfono / WhatsApp de contacto
            <input name="telefono_contacto" type="tel" inputmode="numeric" pattern="[0-9]{9}" maxlength="9" oninput="this.value=this.value.replace(/[^0-9]/g, '').slice(0,9)" value="<?= esc(old('telefono_contacto', $duplicada['telefono_contacto'] ?? '')) ?>" required>
        </label>
    </div>
    <h2>Selección de servicios</h2>
    <?php $serviciosAnteriores = old('servicios', $duplicada !== null ? array_map(static fn ($indice): string => 'origen-' . $indice, array_keys($duplicada['servicios'])) : []); ?>
    <?php $serviciosAnteriores = is_array($serviciosAnteriores) ? array_map('strval', $serviciosAnteriores) : []; ?>
    <?php $serviciosCatalogoConPrecio = array_filter($serviciosCatalogo, static fn ($servicio): bool => (float) $servicio['precio_interno'] > 0); ?>
    <?php $hayServicios = $duplicada !== null ? ($duplicada['servicios'] !== [] || $serviciosCatalogo !== []) : $serviciosCatalogoConPrecio !== []; ?>
    <?php if (! $hayServicios && $duplicada !== null): ?>
        <p>La cotización original no contiene servicios y el catálogo está vacío.</p>
    <?php elseif (! $hayServicios && $serviciosCatalogo === []): ?>
        <p>No hay servicios activos en el catálogo. Configura el catálogo antes de crear cotizaciones.</p>
    <?php elseif (! $hayServicios): ?>
        <p>Configura un precio interno mayor que cero en el catálogo para los servicios antes de cotizar.</p>
    <?php else: ?>
        <div class="service-selection">
            <?php if ($duplicada !== null): ?>
                <?php
                $idsOriginales = [];
                foreach ($duplicada['servicios'] as $indice => $servicio) {
                    if (isset($servicio['servicio_id'])) {
                        $idsOriginales[(string) $servicio['servicio_id']] = true;
                    }
                    $clave = 'origen-' . $indice;
                    $precioCopiado = (float) ($servicio['precio_unitario'] ?? 0);
                ?>
                <div class="service-selection-row">
                    <label><input class="service-checkbox" type="checkbox" name="servicios[]" value="<?= esc($clave) ?>" data-price="<?= esc((string) $precioCopiado) ?>" <?= in_array($clave, $serviciosAnteriores, true) ? 'checked' : '' ?>> <?= esc($servicio['nombre'] ?? '') ?></label>
                    <span>Precio congelado copiado: S/ <?= number_format($precioCopiado, 2) ?> por alumno</span>
                </div>
                <?php } ?>
            <?php endif; ?>
            <?php foreach ($duplicada !== null ? $serviciosCatalogo : $serviciosCatalogoConPrecio as $servicio): ?>
                <?php if ($duplicada !== null && isset($idsOriginales[(string) $servicio['id']])) { continue; } ?>
                <?php $idServicio = (string) $servicio['id']; ?>
                <div class="service-selection-row">
                    <label><input class="service-checkbox" type="checkbox" name="servicios[]" value="<?= esc($idServicio) ?>" <?= in_array($idServicio, $serviciosAnteriores, true) ? 'checked' : '' ?>> <?= esc($servicio['nombre_item']) ?></label>
                    <label class="service-price-field">Precio de cotización por alumno (S/)
                        <input class="service-price" name="precios[<?= esc($idServicio) ?>]" type="number" min="<?= (float) $servicio['precio_interno'] >= 1 ? '1' : '0' ?>" max="<?= esc($servicio['precio_interno']) ?>" step="1" onfocus="this.select()" value="<?= esc(old('precios.' . $idServicio, $servicio['precio_interno'])) ?>">
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p class="quote-estimate" aria-live="polite">Total estimado: S/ <strong id="total-estimado">0.00</strong></p>
    </div>
    <div class="modal-footer">
        <a class="button secondary" href="<?= $urlCerrar ?>">Cancelar</a>
        <button class="button primary" type="submit" <?= ! $hayServicios ? 'disabled' : '' ?>><?= $duplicada !== null ? 'Crear nueva versión' : 'Crear cotización' ?></button>
    </div>
</form>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
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
                const precioInput = checkbox.closest('.service-selection-row').querySelector('.service-price');
                const precio = checkbox.dataset.price !== undefined
                    ? Number(checkbox.dataset.price)
                    : Number(precioInput ? precioInput.value : 0);
                return total + Math.max(0, precio || 0);
            }, 0);

        totalOutput.textContent = (Math.round(precioTotal * cantidad * 100) / 100).toFixed(2);
    };

    form.addEventListener('input', actualizarTotal);
    form.addEventListener('change', actualizarTotal);
    actualizarTotal();

    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }
        form.dataset.submitting = 'true';
        form.classList.add('form-submitting');
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('input[type="checkbox"][name]').forEach((checkbox) => {
            if (checkbox.checked) {
                const selectedService = document.createElement('input');
                selectedService.type = 'hidden';
                selectedService.name = checkbox.name;
                selectedService.value = checkbox.value;
                form.append(selectedService);
            }
            checkbox.disabled = true;
        });
        form.querySelectorAll('input:not([type="checkbox"]):not([type="hidden"])').forEach((input) => { input.readOnly = true; });
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.textContent = 'Guardando…';
        });
    });
})();
</script>
<?= $this->endSection() ?>