<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Cotizaciones - Piero Producciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/cotizaciones.css') ?>">
</head>
<body>

<div class="cotizador-container">
    <div class="header-cotizacion" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <span class="badge-top"><i class="fa-solid fa-list"></i> MÓDULO DE PRUEBAS</span>
            <h1 style="font-size: 26px; margin-top: 8px;">Cotizaciones Registradas</h1>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="<?= base_url('/') ?>" style="display: inline-flex; align-items: center; gap: 8px; background: #1a1a1a; border: 1px solid #333; color: #aaa; padding: 12px 18px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; line-height: 1; box-sizing: border-box;">
                <i class="fa-solid fa-arrow-left"></i> Volver al Inicio
            </a>
            <a href="<?= base_url('cotizaciones/crear') ?>" class="btn-gold" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; width: auto; padding: 12px 20px; margin: 0; line-height: 1; box-sizing: border-box;">
                <i class="fa-solid fa-plus"></i> Nueva Cotización
            </a>
        </div>
    </div>

    <?php if (session()->has('mensaje')): ?>
        <div style="background: rgba(212, 175, 55, 0.15); border: 1px solid #d4af37; padding: 12px; border-radius: 8px; margin-bottom: 20px; color: #d4af37; font-size: 14px;">
            <?= session()->get('mensaje') ?>
        </div>
    <?php endif; ?>

    <?php 
    $cotizaciones = session()->get('cotizaciones_temp') ?? [];
    if (!empty($cotizaciones)): 
        foreach ($cotizaciones as $cot):
    ?>
        <div class="card-box" style="margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <h3 style="color: #d4af37; font-size: 18px;"><?= esc($cot['nombre_colegio']) ?></h3>
                    <p style="font-size: 13px; color: #888;"><?= esc($cot['grado']) ?> - Sección: <?= esc($cot['seccion']) ?> | Sesiones: <?= esc($cot['sesiones']) ?></p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 20px; font-weight: 700; color: #fff;">S/ <?= number_format($cot['total_seccion'], 2) ?></div>
                    <div style="font-size: 11px; color: #888;"><?= $cot['cantidad_alumnos'] ?> Alumnos (S/ <?= number_format($cot['precio_unitario'], 2) ?> c/u)</div>
                </div>
            </div>

            <div style="display: flex; gap: 10px; border-top: 1px solid #222; padding-top: 12px;">
                <a href="<?= base_url('cotizaciones/editar/' . $cot['id']) ?>" style="background: rgba(212, 175, 55, 0.15); border: 1px solid #d4af37; color: #d4af37; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;">
                    <i class="fa-solid fa-pen-to-square"></i> Editar
                </a>
                <a href="<?= base_url('cotizaciones/eliminar/' . $cot['id']) ?>" style="background: #222; border: 1px solid #444; color: #ff5555; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px;">
                    <i class="fa-solid fa-trash"></i> Eliminar
                </a>
            </div>
        </div>
    <?php 
        endforeach;
    else: 
    ?>
        <div class="card-box" style="text-align: center; padding: 40px; color: #888;">
            <i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 10px; color: #444;"></i>
            <p>No hay ninguna cotización de prueba guardada todavía.</p>
            <a href="<?= base_url('cotizaciones/crear') ?>" style="color: #d4af37; font-size: 14px; display: inline-block; margin-top: 10px;">Crear una ahora &rarr;</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>