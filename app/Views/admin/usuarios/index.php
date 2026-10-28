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
                        <label><input type="checkbox" name="permisos[]" value="<?= esc($permission) ?>" <?= in_array($permission, old('permisos') ?? [], true) ? 'checked' : '' ?>><?= esc($label) ?></label>
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
                <thead><tr><th>Nombre</th><th>Usuario</th><th>Cargo</th><th>Rol</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= esc($usuario['nombre'] ?: $usuario['usuario']) ?></td>
                            <td><?= esc($usuario['usuario']) ?></td>
                            <td><?= esc($usuario['cargo'] ?: 'Sin cargo') ?></td>
                            <td><?= esc($usuario['rol']) ?></td>
                            <td><?= (int) $usuario['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
