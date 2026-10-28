<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($this->renderSection('titulo')) ?> | Administración - Piero Producciones</title>
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
</head>
<body>
    <header class="admin-header">
        <a class="admin-brand" href="<?= base_url('admin/cotizaciones') ?>">Piero <span>Administración</span></a>
        <?php if (session()->get('logged_in')): ?>
            <nav>
                <a href="<?= base_url('admin/cotizaciones') ?>">Cotizaciones</a>
                <span><?= esc(session()->get('username')) ?></span>
                <form action="<?= base_url('logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="nav-logout" type="submit">Salir</button>
                </form>
            </nav>
        <?php endif; ?>
    </header>
    <main class="admin-main">
        <?= $this->renderSection('contenido') ?>
    </main>
</body>
</html>
