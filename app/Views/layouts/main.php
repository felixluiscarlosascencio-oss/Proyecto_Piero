<?php helper('contacto'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('titulo') ?> | Piero Producciones</title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>
<body>
    <header class="cabecera">
        <div class="contenedor cabecera__interior">
            <a class="logo" href="<?= base_url() ?>">Piero <span>Producciones</span></a>
            <nav class="menu">
                <a href="<?= base_url() ?>">Inicio</a>
                <a href="<?= base_url('#servicios') ?>">Servicios</a>
                <a href="<?= base_url('cotizaciones') ?>" style="color: #d4af37; font-weight: 600;">Cotizaciones</a>
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