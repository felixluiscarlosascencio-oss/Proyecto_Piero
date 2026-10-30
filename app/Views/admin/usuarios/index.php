<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Crear usuarios<?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<div class="page-heading">
    <div>
        <h1>Equipo de trabajo</h1>
        <p>Administra las cuentas internas y sus permisos.</p>
    </div>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?><div class="alert success"><?= esc($mensaje) ?></div><?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>

<section class="panel quote-form">
    <h2>Crear cuenta del equipo</h2>
    <?php $permisosAnteriores = old('permisos'); ?>
    <?php if (! is_array($permisosAnteriores)): ?><?php $permisosAnteriores = []; ?><?php endif; ?>
    <form action="<?= base_url('admin/usuarios') ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <label class="form-field">Nombre
                <input name="nombre" maxlength="100" value="<?= esc(old('nombre')) ?>" required>
            </label>
            <label class="form-field">Usuario
                <input name="usuario" maxlength="50" value="<?= esc(old('usuario')) ?>" autocomplete="off" required>
            </label>
            <label class="form-field">Contraseña
                <input name="password" type="password" minlength="10" maxlength="255" autocomplete="new-password" required>
            </label>
            <label class="form-field">Cargo
                <input name="cargo" maxlength="80" placeholder="Ej. Fotógrafo" value="<?= esc(old('cargo')) ?>" required>
            </label>
            <label class="form-field full">Rol
                <select name="rol" required>
                    <option value="">Selecciona un rol</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= esc($rol) ?>" <?= old('rol') === $rol ? 'selected' : '' ?>><?= esc($rol) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <fieldset class="form-field full">
                <legend>Permisos específicos</legend>
                <div class="permission-list">
                    <?php foreach ($permissions as $permission => $label): ?>
                        <label><input type="checkbox" name="permisos[]" value="<?= esc($permission) ?>" <?= in_array($permission, $permisosAnteriores, true) ? 'checked' : '' ?>><?= esc($label) ?></label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        </div>
        <button class="button primary" type="submit">Crear usuario</button>
    </form>
</section>

<section class="panel">
    <h2>Cuentas existentes</h2>
    <?php if ($usuarios === []): ?>
        <p>No hay cuentas registradas.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Nombre</th><th>Usuario</th><th>Cargo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= esc($usuario['nombre'] ?: $usuario['usuario']) ?></td>
                            <td><?= esc($usuario['usuario']) ?></td>
                            <td><?= esc($usuario['cargo'] ?: 'Sin cargo') ?></td>
                            <td><?= esc($usuario['rol']) ?></td>
                            <td><?= (int) $usuario['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                            <td>
                                <?php if (! in_array($usuario['rol'], ['Dueno', 'Admin'], true)): ?>
                                <details>
                                    <summary>Editar</summary>
                                    <form class="quote-form" action="<?= base_url('admin/usuarios/' . $usuario['id']) ?>" method="post">
                                        <?= csrf_field() ?>
                                        <label class="form-field">Nombre
                                            <input name="nombre" maxlength="100" value="<?= esc($usuario['nombre']) ?>" required>
                                        </label>
                                        <label class="form-field">Cargo
                                            <input name="cargo" maxlength="80" value="<?= esc($usuario['cargo']) ?>" required>
                                        </label>
                                        <label class="form-field">Rol
                                            <select name="rol" required>
                                                <?php foreach ($roles as $rol): ?>
                                                    <option value="<?= esc($rol) ?>" <?= $usuario['rol'] === $rol ? 'selected' : '' ?>><?= esc($rol) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <?php $permisosUsuario = json_decode($usuario['permisos_json'] ?? '[]', true); ?>
                                        <?php if (! is_array($permisosUsuario)): ?><?php $permisosUsuario = []; ?><?php endif; ?>
                                        <fieldset class="form-field">
                                            <legend>Permisos específicos</legend>
                                            <div class="permission-list">
                                                <?php foreach ($permissions as $permission => $label): ?>
                                                    <label><input type="checkbox" name="permisos[]" value="<?= esc($permission) ?>" <?= in_array($permission, $permisosUsuario, true) ? 'checked' : '' ?>><?= esc($label) ?></label>
                                                <?php endforeach; ?>
                                            </div>
                                        </fieldset>
                                        <button class="button primary" type="submit">Guardar cambios</button>
                                    </form>
                                </details>
                                <?php endif; ?>
                                <?php if ((int) ($usuario['creado_por'] ?? 0) === (int) session()->get('id_usuario') && (int) $usuario['id'] !== (int) session()->get('id_usuario') && ! in_array($usuario['rol'], ['Dueno', 'Admin'], true)): ?>
                                    <button class="button danger compact" type="button" data-delete-url="<?= base_url('admin/usuarios/' . $usuario['id'] . '/eliminar') ?>" data-delete-label="<?= esc($usuario['usuario']) ?>">Eliminar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<dialog id="delete-dialog" aria-labelledby="delete-title">
    <form id="delete-form" method="post">
        <?= csrf_field() ?>
        <h2 id="delete-title">Confirmar eliminación</h2>
        <p id="delete-message"></p>
        <div class="dialog-actions">
            <button class="button secondary" type="button" data-close-dialog>Cancelar</button>
            <button class="button danger" type="submit">Confirmar</button>
        </div>
    </form>
</dialog>
<script>
    const deleteDialog = document.getElementById('delete-dialog');
    const deleteForm = document.getElementById('delete-form');
    document.querySelectorAll('[data-delete-url]').forEach((button) => {
        button.addEventListener('click', () => {
            deleteForm.action = button.dataset.deleteUrl;
            document.getElementById('delete-message').textContent = `¿Eliminar la cuenta ${button.dataset.deleteLabel}? Esta acción no se puede deshacer.`;
            deleteDialog.showModal();
        });
    });
    document.querySelector('[data-close-dialog]')?.addEventListener('click', () => deleteDialog.close());
</script>
<?= $this->endSection() ?>
