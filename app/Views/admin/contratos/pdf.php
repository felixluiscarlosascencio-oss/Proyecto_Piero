<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { color: #222; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { color: #9c7512; font-size: 22px; margin-bottom: 4px; }
        h2 { border-bottom: 1px solid #ccc; font-size: 14px; margin: 20px 0 8px; padding-bottom: 4px; }
        p { margin: 5px 0; }
        table { border-collapse: collapse; margin-top: 8px; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 7px; text-align: left; }
        th { background: #f2f2f2; }
        .meta { color: #555; margin-bottom: 18px; }
        .total { font-size: 13px; font-weight: bold; text-align: right; }
        .signatures { margin-top: 70px; width: 100%; }
        .signatures td { border: 0; padding: 8px 18px; text-align: center; vertical-align: bottom; width: 50%; }
        .signature-line { border-top: 1px solid #555; padding-top: 7px; }
    </style>
</head>
<body>
    <h1>PIERO PRODUCCIONES</h1>
    <p><strong>CONTRATO <?= esc($contrato['numero_contrato'] ?: '#' . $contrato['id']) ?></strong></p>
    <div class="meta">
        <p><strong>Institución:</strong> <?= esc($contrato['nombre_colegio'] ?? 'Sin especificar') ?></p>
        <p><strong>Promoción / sección:</strong> <?= esc($contrato['nombre_seccion'] ?? 'Sin especificar') ?></p>
        <p><strong>Persona encargada:</strong> <?= esc($contrato['persona_encargada'] ?? 'Sin especificar') ?></p>
        <p><strong>Teléfono / WhatsApp:</strong> <?= esc($contrato['telefono_contacto'] ?? 'Sin especificar') ?></p>
        <p><strong>Nivel / aula:</strong> <?= esc(trim(($contrato['nivel'] ?? '') . ' ' . ($contrato['aula'] ?? '')) ?: 'Sin especificar') ?></p>
        <p><strong>Alumnos iniciales:</strong> <?= esc($contrato['cant_alumnos_inicial'] ?? 0) ?></p>
        <p><strong>Fecha:</strong> <?= esc(! empty($contrato['fecha_contrato']) ? date('d/m/Y', strtotime($contrato['fecha_contrato'])) : 'Sin fecha') ?></p>
    </div>

    <h2>Servicios contratados</h2>
    <?php if ($contrato['servicios'] === []): ?>
        <p>Sin servicios registrados.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>Servicio</th><th>Precio por alumno</th></tr></thead>
            <tbody>
                <?php foreach ($contrato['servicios'] as $servicio): ?>
                    <?php if (! is_array($servicio)) { continue; } ?>
                    <tr>
                        <td><?= esc($servicio['nombre'] ?? '') ?></td>
                        <td>S/ <?= number_format((float) ($servicio['precio_unitario'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <p class="total">Monto original: S/ <?= number_format((float) ($contrato['monto_total'] ?? 0), 2) ?></p>

    <h2>Programación</h2>
    <?php if ($contrato['agenda'] === []): ?>
        <p>Sin agenda registrada.</p>
    <?php else: ?>
        <?php foreach ($contrato['agenda'] as $sesion): ?>
            <?php if (is_array($sesion)): ?><p><?= esc($sesion['fecha'] ?? '') ?> · <?= esc($sesion['detalle'] ?? '') ?></p><?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>Cuadros</h2>
    <?php if ($contrato['cuadros'] === []): ?>
        <p>Sin cuadros registrados.</p>
    <?php else: ?>
        <?php foreach ($contrato['cuadros'] as $cuadro): ?>
            <?php if (is_array($cuadro)): ?><p><?= esc($cuadro['detalle'] ?? '') ?> · Cantidad: <?= esc($cuadro['cantidad'] ?? 0) ?></p><?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (! empty($contrato['observaciones'])): ?>
        <h2>Observaciones</h2>
        <p><?= nl2br(esc($contrato['observaciones'])) ?></p>
    <?php endif; ?>

    <table class="signatures">
        <tr>
            <td><div class="signature-line">Piero Producciones</div></td>
            <td><div class="signature-line"><?= esc($contrato['persona_encargada'] ?? 'Representante de la institución') ?></div></td>
        </tr>
    </table>
</body>
</html>
