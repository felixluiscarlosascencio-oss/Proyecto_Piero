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
            <a class="admin-brand" href="<?= base_url('admin/dashboard') ?>">PIERO <span>PRODUCCIONES</span></a>
            <div class="sidebar-user">
                <span class="user-caption">Sesión interna</span>
                <strong><?= esc(session()->get('nombre') ?: session()->get('username')) ?></strong>
                <span><?= esc(session()->get('cargo') ?: session()->get('rol')) ?></span>
                <small>Rol: <?= esc(session()->get('rol')) ?></small>
            </div>
            <?php $rutaActual = trim(uri_string(), '/'); ?>
            <nav class="admin-nav" aria-label="Menú administrativo">
                <a href="<?= base_url('admin/dashboard') ?>" <?= $rutaActual === 'admin/dashboard' || $rutaActual === 'admin' ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="11" width="7" height="10" rx="1"/><rect x="3" y="14" width="8" height="7" rx="1"/></svg>Dashboard</a>
                <a href="<?= base_url('admin/cotizaciones') ?>" <?= $rutaActual === 'admin/cotizaciones' ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M10 13h6M10 17h6"/></svg>Cotizaciones</a>
                <a href="<?= base_url('admin/cotizaciones/vigentes') ?>" <?= $rutaActual === 'admin/cotizaciones/vigentes' ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>Cotizaciones vigentes</a>
                <?php if (in_array(session()->get('rol'), ['Dueno', 'Admin'], true)): ?>
                    <a href="<?= base_url('admin/servicios') ?>" <?= str_starts_with($rutaActual, 'admin/servicios') ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16l-1 13H5L4 7zM9 7a3 3 0 0 1 6 0"/></svg>Servicios</a>
                    <a href="<?= base_url('admin/solicitudes') ?>" <?= str_starts_with($rutaActual, 'admin/solicitudes') ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v13H8l-4 3zM8 9h8M8 13h5"/></svg>Solicitudes del equipo</a>
                <?php endif; ?>
                <?php if (\App\Libraries\AdminAccess::can('contratos.ver')): ?>
                    <a href="<?= base_url('admin/contratos') ?>" <?= str_starts_with($rutaActual, 'admin/contratos') ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14v18H5zM8 8h8M8 12h8M8 16h5"/></svg>Contratos</a>
                <?php endif; ?>
                <?php if (in_array(session()->get('rol'), ['Dueno', 'Admin'], true)): ?>
                    <a href="<?= base_url('admin/usuarios') ?>" <?= str_starts_with($rutaActual, 'admin/usuarios') ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M17 8h4M19 6v4"/></svg>Usuarios</a>
                    <a href="<?= base_url('admin/reportes') ?>" <?= $rutaActual === 'admin/reportes' ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>Reportes</a>
                <?php endif; ?>
            </nav>
            <form class="sidebar-logout" action="<?= base_url('logout') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg>Cerrar sesión</button>
            </form>
        </aside>
        <main class="admin-main">
            <header class="admin-topbar">
                <form class="global-search" action="<?= base_url('admin/buscar') ?>" method="get" role="search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
                    <?php $busquedaActual = service('request')->getGet('q'); ?>
                    <input type="search" name="q" value="<?= esc(is_string($busquedaActual) ? $busquedaActual : '') ?>" placeholder="Buscar en el panel" aria-label="Búsqueda global">
                    <button type="submit">Buscar</button>
                </form>
                <div class="topbar-actions">
                    <?php if (\App\Libraries\AdminAccess::isAdmin()): ?><a class="button primary compact" href="<?= base_url('admin/cotizaciones/nueva') ?>">+ Nueva cotización</a><?php endif; ?>
                    <details class="admin-menu">
                        <summary><span class="admin-avatar"><?= esc(mb_strtoupper(mb_substr((string) (session()->get('nombre') ?: 'A'), 0, 1))) ?></span><span>Admin</span><span aria-hidden="true">⌄</span></summary>
                        <div class="admin-menu-popover">
                            <strong><?= esc(session()->get('nombre') ?: session()->get('username')) ?></strong>
                            <?php if (in_array(session()->get('rol'), ['Dueno', 'Admin'], true)): ?><a href="<?= base_url('admin/usuarios') ?>">Administrar usuarios</a><?php endif; ?>
                            <form action="<?= base_url('logout') ?>" method="post"><?= csrf_field() ?><button type="submit">Cerrar sesión</button></form>
                        </div>
                    </details>
                </div>
            </header>
            <?= $this->renderSection('contenido') ?>
        </main>
    </div>
</body>
</html>
