<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($this->renderSection('titulo')) ?> | Administración - Piero Producciones</title>
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a class="admin-brand" href="<?= base_url('admin/cotizaciones') ?>">PIERO <span>PRODUCCIONES</span></a>
            <div class="sidebar-user">
                <span class="user-caption">Sesión interna</span>
                <strong><?= esc(session()->get('nombre') ?: session()->get('username')) ?></strong>
                <span><?= esc(session()->get('cargo') ?: session()->get('rol')) ?></span>
                <small>Rol: <?= esc(session()->get('rol')) ?></small>
            </div>
            <nav class="admin-nav" aria-label="Menú administrativo">
                <a href="<?= base_url('admin/cotizaciones') ?>">Cotizaciones</a>
                <a href="<?= base_url('admin/cotizaciones/vigentes') ?>">Cotizaciones vigentes</a>
                <?php if (in_array(session()->get('rol'), ['Dueno', 'Admin'], true)): ?>
                    <a href="<?= base_url('admin/usuarios') ?>">Crear usuarios</a>
                    <a href="<?= base_url('admin/solicitudes') ?>">Solicitudes del equipo</a>
                <?php endif; ?>
                <?php if (\App\Libraries\AdminAccess::can('contratos.ver')): ?>
                    <a href="<?= base_url('admin/contratos') ?>">Contratos</a>
                <?php endif; ?>
            </nav>
            <form class="sidebar-logout" action="<?= base_url('logout') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit">Cerrar sesión</button>
            </form>
        </aside>
        <main class="admin-main">
            <?= $this->renderSection('contenido') ?>
        </main>
    </div>
</body>
</html>
