<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Contratos<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <h1>Contratos</h1>
        <p>Consulta los acuerdos y administra sus datos y alumnos.</p>
    </div>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?><div class="alert success"><?= esc($mensaje) ?></div><?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>

<?php if ($contratos === []): ?>
    <section class="panel empty-state">No hay contratos registrados.</section>
<?php else: ?>
    <section class="panel table-wrap">
        <table class="data-table">
            <thead><tr><th>Contrato</th><th>Nivel / aula</th><th>Alumnos</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($contratos as $contrato): ?>
                    <?php $estado = (string) ($contrato['estado_contrato'] ?? 'Activo'); ?>
                    <?php $cerrado = in_array(mb_strtolower(trim($estado)), ['cerrado', 'cerrada', 'finalizado', 'finalizada'], true); ?>
                    <tr>
                        <td><?= esc($contrato['numero_contrato'] ?: '#' . $contrato['id']) ?></td>
                        <td><?= esc(trim(($contrato['nivel'] ?? '') . ' ' . ($contrato['aula'] ?? '')) ?: 'Sin especificar') ?></td>
                        <td><?= esc($contrato['cant_alumnos_inicial'] ?? 0) ?></td>
                        <td><span class="status status-<?= $cerrado ? 'denegada' : 'aprobado' ?>"><?= esc($estado) ?></span></td>
                        <td>
                            <div class="inline-actions">
                                <a class="button secondary compact" href="<?= base_url('admin/contratos/' . $contrato['id']) ?>">Ver / editar</a>
                                <?php if (\App\Libraries\AdminAccess::isOwner()): ?>
                                    <button class="button danger compact" type="button" data-delete-url="<?= base_url('admin/contratos/' . $contrato['id'] . '/eliminar') ?>" data-delete-label="contrato <?= esc($contrato['numero_contrato'] ?: '#' . $contrato['id']) ?>">Eliminar</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>

<?php if (\App\Libraries\AdminAccess::isOwner()): ?>
    <dialog id="delete-dialog" aria-labelledby="delete-title">
        <form id="delete-form" method="post">
            <?= csrf_field() ?>
            <h2 id="delete-title">Confirmar eliminación</h2>
            <p id="delete-message"></p>
            <div class="dialog-actions">
                <button class="button secondary" type="button" data-close-dialog>Cancelar</button>
                <button class="button danger" type="submit">Confirmar eliminación</button>
            </div>
        </form>
    </dialog>
    <script>
        const deleteDialog = document.getElementById('delete-dialog');
        const deleteForm = document.getElementById('delete-form');
        const deleteMessage = document.getElementById('delete-message');
        document.querySelectorAll('[data-delete-url]').forEach((button) => {
            button.addEventListener('click', () => {
                deleteForm.action = button.dataset.deleteUrl;
                deleteMessage.textContent = `¿Eliminar ${button.dataset.deleteLabel}?`;
                deleteDialog.showModal();
            });
        });
        document.querySelector('[data-close-dialog]')?.addEventListener('click', () => deleteDialog.close());
    </script>
<?php endif; ?>
<?= $this->endSection() ?>
