<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= $servicio === null ? 'Nuevo servicio' : 'Editar servicio' ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php
$error = session()->getFlashdata('error');
$valor = static function (string $campo, mixed $predeterminado = '') use ($servicio): string {
    $anterior = old($campo);
    if ($anterior !== null) {
        return is_scalar($anterior) ? (string) $anterior : '';
    }

    return (string) ($servicio[$campo] ?? $predeterminado);
};
$galeria = $servicio === null ? [] : json_decode($servicio['galeria_json'] ?? '[]', true);
$galeria = is_array($galeria) ? $galeria : [];
?>
<div class="modal show service-modal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="service-modal-title" style="display:block">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="service-modal-title"><?= $servicio === null ? 'Nuevo servicio' : 'Editar servicio' ?></h1>
                <a class="btn-close" href="<?= base_url('admin/servicios') ?>" aria-label="Cerrar"></a>
            </div>
            <form class="service-form" action="<?= $servicio === null ? base_url('admin/servicios') : base_url('admin/servicios/' . $servicio['id']) ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <?php if ($error): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>
                    <div class="form-grid">
                        <label class="form-field full">Nombre del servicio
                            <input name="nombre_item" maxlength="100" value="<?= esc($valor('nombre_item')) ?>" required>
                        </label>
                        <label class="form-field full">Descripción breve
                            <textarea name="descripcion" maxlength="16000" rows="3"><?= esc($valor('descripcion')) ?></textarea>
                        </label>
                        <label class="form-field full">Detalle largo
                            <textarea class="service-long-detail" name="detalle_largo" maxlength="16000"><?= esc($valor('detalle_largo')) ?></textarea>
                        </label>
                        <label class="form-field">Etiqueta destacada <span>(opcional, por ejemplo “Promoción”)</span>
                            <input name="etiqueta_destacada" maxlength="100" value="<?= esc($valor('etiqueta_destacada')) ?>">
                        </label>
                        <label class="form-field">Precio interno (solo administración)
                            <input name="precio_interno" type="number" min="0" max="99999999.99" step="1" onfocus="this.select()" value="<?= esc($valor('precio_interno', '0.00')) ?>" required>
                        </label>
                        <div class="form-field full">
                            <span>Agregar imágenes a la galería (JPG, PNG o WebP; máximo 5 MB cada una)</span>
                            <div class="service-image-picker">
                                <input class="visually-hidden" id="galeria" name="galeria[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                                <label class="button secondary" for="galeria">Elegir Imagen</label>
                                <span id="galeria-resumen">Sin imágenes seleccionadas</span>
                            </div>
                        </div>
                    </div>
                    <?php if ($galeria !== []): ?>
                        <h2 class="mt-4">Galería actual</h2>
                        <div class="service-admin-gallery">
                            <?php foreach ($galeria as $indice => $imagen): ?>
                                <?php if (is_string($imagen) && $imagen !== ''): ?>
                                    <div class="service-gallery-item">
                                        <img src="<?= esc(base_url(ltrim($imagen, '/'))) ?>" alt="Imagen actual de <?= esc($servicio['nombre_item']) ?>">
                                        <label><input type="checkbox" name="eliminar_imagenes[]" value="<?= (int) $indice ?>"> Quitar</label>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <a class="button secondary" href="<?= base_url('admin/servicios') ?>">Cancelar</a>
                    <button class="button primary" type="submit"><?= $servicio === null ? 'Crear servicio' : 'Guardar cambios' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
<script>
    document.getElementById('galeria')?.addEventListener('change', (event) => {
        const count = event.currentTarget.files.length;
        document.getElementById('galeria-resumen').textContent = count ? `${count} archivo(s) seleccionado(s)` : 'Sin imágenes seleccionadas';
    });
</script>
<?= $this->endSection() ?>
