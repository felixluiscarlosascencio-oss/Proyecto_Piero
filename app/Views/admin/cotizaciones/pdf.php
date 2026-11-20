<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { color: #222; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { color: #9c7512; font-size: 24px; margin-bottom: 2px; }
        h2 { font-size: 15px; margin: 0 0 14px; }
        .meta { margin: 20px 0; }
        .meta p { margin: 4px 0; }
        table { border-collapse: collapse; margin-top: 18px; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 9px; text-align: left; }
        th { background: #f2f2f2; }
        .total { font-size: 14px; font-weight: bold; text-align: right; }
        .footer { color: #666; font-size: 10px; margin-top: 26px; }
    </style>
</head>
<body>
    <?php
    $codigo = (string) ($cotizacion['codigo_cotizacion'] ?? ('COT-' . ($cotizacion['id'] ?? '')));
    $venceEn = $cotizacion['vence_en'] ?? null;
    ?>
    <h1>Piero Producciones</h1>
    <h2>Cotización <?= esc($codigo) ?> · Versión <?= (int) ($cotizacion['version'] ?? 1) ?></h2>
    <div class="meta">
        <p><strong>Institución:</strong> <?= esc($cotizacion['nombre_colegio']) ?></p>
        <p><strong>Promoción / sección:</strong> <?= esc($cotizacion['nombre_seccion'] ?: 'No especificada') ?></p>
        <p><strong>Estudiantes:</strong> <?= esc($cotizacion['cantidad_estudiantes']) ?></p>
        <p><strong>Persona encargada:</strong> <?= esc($cotizacion['persona_encargada'] ?: 'Sin dato') ?></p>
        <p><strong>Teléfono / WhatsApp:</strong> <?= esc($cotizacion['telefono_contacto'] ?: 'Sin dato') ?></p>
        <p><strong>Fecha de emisión:</strong> <?= esc(! empty($cotizacion['created_at']) ? date('d/m/Y', strtotime($cotizacion['created_at'])) : date('d/m/Y')) ?></p>
        <p><strong>Válida hasta:</strong> <?= $venceEn ? esc(date('d/m/Y', strtotime($venceEn))) : 'Sin vigencia definida' ?></p>
    </div>
    <table>
        <thead>
            <tr><th>Servicio</th><th>Precio por estudiante</th></tr>
        </thead>
        <tbody>
            <?php foreach ($cotizacion['servicios'] as $servicio): ?>
                <tr>
                    <td><?= esc($servicio['nombre'] ?? '') ?></td>
                    <td>S/ <?= number_format((float) ($servicio['precio_unitario'] ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="total">Total por estudiante: S/ <?= number_format((float) $cotizacion['precio_unitario'], 2) ?></p>
    <p class="total">Total para <?= esc($cotizacion['cantidad_estudiantes']) ?> estudiantes: S/ <?= number_format((float) $cotizacion['total_seccion'], 2) ?></p>
    <p class="footer">Documento generado por Piero Producciones. El código y la versión identifican de forma permanente esta propuesta.</p>
</body>
</html>
