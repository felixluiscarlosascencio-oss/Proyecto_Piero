<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Solicitudes del equipo<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <h1>Solicitudes del equipo</h1>
        <p>Revisa cada cambio o eliminación antes de aplicarlo.</p>
    </div>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?><div class="alert success"><?= esc($mensaje) ?></div><?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>

<?php if ($solicitudes === []): ?>
    <section class="panel empty-state">No hay solicitudes del equipo.</section>
<?php else: ?>
    <div class="record-list">
        <?php foreach ($solicitudes as $solicitud): ?>
            <article class="panel">
                <div class="quote-heading">
                    <div>
                        <span class="quote-id">Solicitud #<?= esc($solicitud['id']) ?></span>
                        <h2><?= $solicitud['accion'] === 'eliminar' ? 'Eliminar' : 'Edición no disponible' ?> cotización #<?= esc($solicitud['entidad_id']) ?></h2>
                    </div>
                    <span class="status status-<?= esc($solicitud['estado']) ?>"><?= esc(ucfirst($solicitud['estado'])) ?></span>
                </div>
                <p><strong>Solicitante:</strong> <?= esc($solicitud['solicitante_nombre'] ?: $solicitud['solicitante_usuario'] ?: 'Cuenta de equipo') ?></p>
                <p><strong>Fecha:</strong> <?= esc($solicitud['created_at'] ? date('d/m/Y H:i', strtotime($solicitud['created_at'])) : 'Sin fecha') ?></p>
                <?php if ($solicitud['accion'] === 'editar'): ?>
                    <p>Esta solicitud de edición pertenece al flujo anterior y ya no se puede aprobar.</p>
                <?php endif; ?>
                <?php if ($solicitud['estado'] === 'pendiente'): ?>
                    <div class="inline-actions">
                        <?php if ($solicitud['accion'] === 'eliminar'): ?>
                            <form action="<?= base_url('admin/solicitudes/' . $solicitud['id'] . '/aprobar') ?>" method="post">
                                <?= csrf_field() ?>
                                <button class="button primary" type="submit">Confirmar eliminación</button>
                            </form>
                        <?php endif; ?>
                        <form action="<?= base_url('admin/solicitudes/' . $solicitud['id'] . '/denegar') ?>" method="post">
                            <?= csrf_field() ?>
                            <button class="button secondary" type="submit">Denegar</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p>Resuelta por el administrador<?= $solicitud['resuelto_en'] ? ' el ' . esc(date('d/m/Y H:i', strtotime($solicitud['resuelto_en']))) : '' ?>.</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
