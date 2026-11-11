<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= $servicio === null ? 'Nuevo servicio' : 'Editar servicio' ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <a class="back-link" href="<?= base_url('admin/servicios') ?>">&larr; Volver a servicios</a>
        <h1><?= $servicio === null ? 'Nuevo servicio' : 'Editar servicio' ?></h1>
    </div>
</div>

<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>
<?php
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
<form class="panel quote-form" action="<?= $servicio === null ? base_url('admin/servicios') : base_url('admin/servicios/' . $servicio['id']) ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-grid">
        <label class="form-field full">Nombre del servicio
            <input name="nombre_item" maxlength="100" value="<?= esc($valor('nombre_item')) ?>" required>
        </label>
        <label class="form-field full">Descripción breve
            <textarea name="descripcion" maxlength="16000" rows="3"><?= esc($valor('descripcion')) ?></textarea>
        </label>
        <label class="form-field full">Detalle largo
            <textarea name="detalle_largo" maxlength="16000" rows="6"><?= esc($valor('detalle_largo')) ?></textarea>
        </label>
        <label class="form-field">Etiqueta destacada <span>(opcional, por ejemplo “Promoción”)</span>
            <input name="etiqueta_destacada" maxlength="100" value="<?= esc($valor('etiqueta_destacada')) ?>">
        </label>
        <label class="form-field">Precio interno (solo administración)
            <input name="precio_interno" type="number" min="0" max="99999999.99" step="0.01" value="<?= esc($valor('precio_interno', '0.00')) ?>" required>
        </label>
        <label class="form-field full">Agregar imágenes a la galería <span>(JPG, PNG o WebP; máximo 5 MB cada una)</span>
            <input name="galeria[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
        </label>
    </div>
    <?php if ($galeria !== []): ?>
        <h2>Galería actual</h2>
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
    <div class="action-row">
        <button class="button primary" type="submit"><?= $servicio === null ? 'Crear servicio' : 'Guardar cambios' ?></button>
        <a class="button secondary" href="<?= base_url('admin/servicios') ?>">Cancelar</a>
    </div>
</form>
<?= $this->endSection() ?>
