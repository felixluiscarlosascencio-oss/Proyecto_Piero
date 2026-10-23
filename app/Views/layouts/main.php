<?php helper('contacto'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('titulo') ?> | Piero Producciones</title>
    
    <!-- Íconos y Estilos Principal -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body>
    <header class="cabecera">
        <div class="contenedor cabecera__interior" style="display: flex; justify-content: space-between; align-items: center;">
            <a class="logo" href="<?= base_url() ?>">Piero <span>Producciones</span></a>
            
            <nav class="menu" style="display: flex; align-items: center; gap: 20px;">
                <a href="<?= base_url() ?>">Inicio</a>
                <a href="<?= base_url('#servicios') ?>">Servicios</a>
                <a href="<?= base_url('cotizaciones') ?>" style="color: #d4af37; font-weight: 600;">Cotizaciones</a>

                <!-- ESTADO DE SESIÓN DE USUARIO -->
                <?php if (session()->get('logged_in')): ?>
                    <div style="display: flex; align-items: center; gap: 12px; margin-left: 10px; padding-left: 15px; border-left: 1px solid #333;">
                        <span style="color: #d4af37; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-user-gear"></i> <?= esc(session()->get('username') ?? 'admin') ?>
                        </span>
                        <a href="<?= base_url('logout') ?>" style="background: rgba(235, 87, 87, 0.15); border: 1px solid #eb5757; color: #ff6b6b; padding: 5px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; transition: all 0.2s ease;">
                            <i class="fa-solid fa-right-from-bracket"></i> Salir
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?= base_url('login') ?>" style="background: linear-gradient(135deg, #e5be3e 0%, #c49a22 100%); color: #0b0c0e; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 700; margin-left: 10px;">
                        <i class="fa-solid fa-right-to-bracket"></i> Ingresar
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main>
        <?= $this->renderSection('contenido') ?>
    </main>

    <footer class="pie">
        <div class="contenedor">
            <p>&copy; <?= date('Y') ?> Piero Producciones. Todos los derechos reservados.</p>
        </div>
    </footer>

    <a class="wsp-flotante" href="<?= enlace_wsp() ?>" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp" title="Escríbenos por WhatsApp">
        <?= icono_wsp() ?>
    </a>
</body>
</html>