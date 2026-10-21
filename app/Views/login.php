<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | PIERO PRODUCCIONES</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tu CSS separado desde la carpeta public/css/login.css -->
    <link rel="stylesheet" href="<?= base_url('css/login.css') ?>">
</head>
<body>

<div class="card-login">
    <h3 class="text-center mb-1 fw-bold">PIERO PRODUCCIONES</h3>
    <p class="text-center text-muted mb-4">Ingresa tus credenciales para acceder</p>

    <!-- Alerta de error en caso de credenciales incorrectas -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger p-2 small text-center">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('login') ?>" method="POST">
        <?= csrf_field() ?>
        
        <div class="mb-3">
            <label for="username" class="form-label">Usuario</label>
            <input type="text" name="username" id="username" class="form-control" placeholder="Ej: admin" required autofocus>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-gold w-100 py-2">Ingresar al Sistema</button>
    </form>
</div>

</body>
</html>