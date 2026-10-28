<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { color: #222; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { color: #9c7512; font-size: 24px; }
        .meta { margin: 24px 0; }
        table { border-collapse: collapse; margin-top: 20px; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
        .total { font-size: 16px; font-weight: bold; text-align: right; }
    </style>
</head>
<body>
    <h1>Piero Producciones</h1>
    <h2>Cotización #<?= esc($cotizacion['id']) ?></h2>
    <div class="meta">
        <p><strong>Institución:</strong> <?= esc($cotizacion['nombre_colegio']) ?></p>
        <p><strong>Estudiantes:</strong> <?= esc($cotizacion['cantidad_estudiantes']) ?></p>
        <p><strong>Fecha:</strong> <?= esc(date('d/m/Y')) ?></p>
        <p><strong>Válida hasta:</strong> <?= esc(date('d/m/Y', strtotime($cotizacion['vence_en']))) ?></p>
    </div>
    <table>
        <thead>
            <tr><th>Servicio</th><th>Precio por estudiante</th></tr>
        </thead>
        <tbody>
            <?php foreach ($cotizacion['servicios'] as $servicio): ?>
                <tr>
                    <td><?= esc($servicio['nombre']) ?></td>
                    <td>S/ <?= number_format((float) $servicio['precio_unitario'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="total">Total por estudiante: S/ <?= number_format((float) $cotizacion['precio_unitario'], 2) ?></p>
    <p class="total">Total para <?= esc($cotizacion['cantidad_estudiantes']) ?> estudiantes: S/ <?= number_format((float) $cotizacion['total_seccion'], 2) ?></p>
</body>
</html>
