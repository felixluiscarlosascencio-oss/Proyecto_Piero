<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Piero Producciones</title>

    <!-- Fuentes e Íconos -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos del Login -->
    <link rel="stylesheet" href="<?= base_url('css/login.css') ?>">
</head>
<body>

<div class="login-wrapper">
    
    <!-- PANEL IZQUIERDO: MARCA E IMAGEN -->
    <div class="login-hero">
        <div class="hero-content">
            <div class="brand-logo-text">
                PIERO <span>PRODUCCIONES</span>
            </div>
        </div>
        
        <div class="hero-content hero-text">
            <h2>Capturando momentos únicos que perduran</h2>
            <p>Sistema de gestión interna para cotizaciones, sesiones fotográficas y proyectos escolares de Piero Producciones.</p>
        </div>

        <div class="hero-content hero-copy">
            &copy; <?= date('Y') ?> Piero Producciones. Todos los derechos reservados.
        </div>
    </div>

    <!-- PANEL DERECHO: FORMULARIO -->
    <div class="login-form-container">
        
        <a href="<?= base_url('/') ?>" class="back-home">
            <i class="fa-solid fa-arrow-left"></i> Ir a la web
        </a>

        <div class="form-box">
            
            <div class="form-header">
                <h3>Iniciar Sesión</h3>
                <p>Ingresa tus credenciales para acceder al panel</p>
            </div>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('login') ?>" method="POST">
                <?= csrf_field() ?>
                
                <div class="input-group-custom">
                    <label for="username">Usuario</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="username" name="username" class="input-control" placeholder="Ej. admin" required autofocus>
                    </div>
                </div>

                <div class="input-group-custom">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" class="input-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    Ingresar al Sistema <i class="fa-solid fa-arrow-right"></i>
                </button>

            </form>
        </div>

    </div>

</div>

</body>
</html>