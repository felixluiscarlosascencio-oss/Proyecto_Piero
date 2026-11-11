<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Servicios<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <h1>Servicios</h1>
        <p>Administra el catálogo público y los valores internos para uso administrativo.</p>
    </div>
    <a class="button primary" href="<?= base_url('admin/servicios/nuevo') ?>">Agregar servicio</a>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?><div class="alert success"><?= esc($mensaje) ?></div><?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>

<?php if ($servicios === []): ?>
    <section class="panel empty-state">Aún no hay servicios en el catálogo.</section>
<?php else: ?>
    <div class="service-admin-grid">
        <?php foreach ($servicios as $servicio): ?>
            <?php
            $galeria = json_decode($servicio['galeria_json'] ?? '[]', true);
            $galeria = is_array($galeria) ? $galeria : [];
            ?>
            <article class="panel service-admin-card">
                <h2><?= esc($servicio['nombre_item']) ?></h2>
                <?php if (! empty($servicio['etiqueta_destacada'])): ?><p><strong>Etiqueta:</strong> <?= esc($servicio['etiqueta_destacada']) ?></p><?php endif; ?>
                <p><?= esc($servicio['descripcion'] ?: 'Sin descripción breve.') ?></p>
                <p><strong>Precio interno:</strong> S/ <?= number_format((float) $servicio['precio_interno'], 2) ?></p>
                <?php if ($galeria !== []): ?>
                    <div class="service-admin-gallery">
                        <?php foreach ($galeria as $imagen): ?>
                            <?php if (is_string($imagen) && $imagen !== ''): ?>
                                <img src="<?= esc(base_url(ltrim($imagen, '/'))) ?>" alt="Imagen de <?= esc($servicio['nombre_item']) ?>" loading="lazy">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="action-row">
                    <a class="button secondary" href="<?= base_url('admin/servicios/' . $servicio['id'] . '/editar') ?>">Editar</a>
                    <form action="<?= base_url('admin/servicios/' . $servicio['id'] . '/eliminar') ?>" method="post" onsubmit="return confirm('¿Eliminar este servicio?');">
                        <?= csrf_field() ?>
                        <button class="button danger" type="submit">Eliminar</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
