<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Contrato <?= esc($contrato['numero_contrato'] ?: '#' . $contrato['id']) ?><?= $this->endSection() ?>
<?= $this->section('contenido') ?>
<?php $puedeEditar = \App\Libraries\AdminAccess::can('contratos.editar'); ?>
<div class="page-heading">
    <div>
        <a class="back-link" href="<?= base_url('admin/contratos') ?>">&larr; Volver a contratos</a>
        <h1>Contrato <?= esc($contrato['numero_contrato'] ?: '#' . $contrato['id']) ?></h1>
        <p>Estado: <?= esc($contrato['estado_contrato'] ?? 'Activo') ?></p>
    </div>
</div>

<?php if ($mensaje = session()->getFlashdata('mensaje')): ?><div class="alert success"><?= esc($mensaje) ?></div><?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?><div class="alert error"><?= esc($error) ?></div><?php endif; ?>

<?php if ($puedeEditar): ?>
    <form class="panel quote-form" action="<?= base_url('admin/contratos/' . $contrato['id']) ?>" method="post">
        <?= csrf_field() ?>
        <h2>Datos del contrato</h2>
        <div class="form-grid">
            <label class="form-field">Nivel
                <input name="nivel" maxlength="50" value="<?= esc(old('nivel', $contrato['nivel'] ?? '')) ?>">
            </label>
            <label class="form-field">Aula / sección
                <input name="aula" maxlength="50" value="<?= esc(old('aula', $contrato['aula'] ?? '')) ?>">
            </label>
            <label class="form-field">Cantidad inicial de alumnos
                <input name="cant_alumnos_inicial" type="number" min="1" max="10000" value="<?= esc(old('cant_alumnos_inicial', $contrato['cant_alumnos_inicial'] ?? 1)) ?>" required>
            </label>
            <label class="form-field full">Observaciones
                <textarea name="observaciones" rows="3" maxlength="10000"><?= esc(old('observaciones', $contrato['observaciones'] ?? '')) ?></textarea>
            </label>
        </div>
        <h2>Servicios</h2>
        <div id="contract-services">
            <?php $servicios = $contrato['servicios'] ?: [['nombre' => '', 'precio_unitario' => 0]]; ?>
            <?php foreach ($servicios as $indice => $servicio): ?>
                <div class="service-row">
                    <label>Servicio
                        <input name="servicios[<?= esc($indice) ?>][nombre]" maxlength="120" value="<?= esc($servicio['nombre'] ?? '') ?>" required>
                    </label>
                    <label>Precio por estudiante (S/)
                        <input name="servicios[<?= esc($indice) ?>][precio_unitario]" type="number" min="0" step="0.01" value="<?= esc($servicio['precio_unitario'] ?? 0) ?>" required>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="button secondary" id="add-contract-service" type="button">Agregar servicio</button>

        <h2>Agenda de sesiones</h2>
        <div id="contract-agenda">
            <?php $agenda = $contrato['agenda'] ?: [['fecha' => '', 'detalle' => '']]; ?>
            <?php foreach ($agenda as $indice => $sesion): ?>
                <div class="service-row">
                    <label>Fecha
                        <input name="agenda[<?= esc($indice) ?>][fecha]" type="date" value="<?= esc($sesion['fecha'] ?? '') ?>">
                    </label>
                    <label>Sesión / detalle
                        <input name="agenda[<?= esc($indice) ?>][detalle]" maxlength="500" value="<?= esc($sesion['detalle'] ?? '') ?>" placeholder="Ej. Fotos grupales de primaria">
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="button secondary" id="add-contract-session" type="button">Agregar fecha</button>

        <h2>Cuadros</h2>
        <div id="contract-frames">
            <?php $cuadros = $contrato['cuadros'] ?: [['detalle' => '', 'cantidad' => 1]]; ?>
            <?php foreach ($cuadros as $indice => $cuadro): ?>
                <div class="service-row">
                    <label>Especificación
                        <input name="cuadros[<?= esc($indice) ?>][detalle]" maxlength="300" value="<?= esc($cuadro['detalle'] ?? '') ?>" placeholder="Ej. Retrato 30 x 40 cm">
                    </label>
                    <label>Cantidad
                        <input name="cuadros[<?= esc($indice) ?>][cantidad]" type="number" min="1" max="10000" value="<?= esc($cuadro['cantidad'] ?? 1) ?>">
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="button secondary" id="add-contract-frame" type="button">Agregar cuadro</button>

        <h2>Alumnos</h2>
        <?php if ($alumnos === []): ?><p>Aún no hay alumnos en el padrón.</p><?php endif; ?>
        <?php foreach ($alumnos as $alumno): ?>
            <div class="student-row">
                <div>
                    <strong><?= esc($alumno['nombre_completo']) ?></strong>
                    <small><?= esc($alumno['tipo_registro']) ?> · <?= esc($alumno['estado']) ?><?= $alumno['numero_lista'] ? ' · Lista ' . esc($alumno['numero_lista']) : '' ?></small>
                </div>
                <?php if ($alumno['estado'] === 'Activo'): ?>
                    <button class="button secondary compact" type="submit" name="retirar_alumno_id" value="<?= esc($alumno['id']) ?>">Dar de baja</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <label class="form-field">Alta de última hora
            <input name="nuevo_alumno" maxlength="150" placeholder="Nombre completo del alumno">
        </label>
        <button class="button primary" type="submit">Guardar cambios</button>
    </form>
<?php else: ?>
    <section class="panel">
        <h2>Datos del contrato</h2>
        <p><strong>Nivel / aula:</strong> <?= esc(trim(($contrato['nivel'] ?? '') . ' ' . ($contrato['aula'] ?? '')) ?: 'Sin especificar') ?></p>
        <p><strong>Alumnos iniciales:</strong> <?= esc($contrato['cant_alumnos_inicial'] ?? 0) ?></p>
        <p><strong>Observaciones:</strong> <?= nl2br(esc($contrato['observaciones'] ?? '')) ?></p>
        <h2>Servicios</h2>
        <?php foreach ($contrato['servicios'] as $servicio): ?>
            <p><?= esc($servicio['nombre'] ?? '') ?> · S/ <?= number_format((float) ($servicio['precio_unitario'] ?? 0), 2) ?> por estudiante</p>
        <?php endforeach; ?>
        <h2>Agenda</h2>
        <?php foreach ($contrato['agenda'] as $sesion): ?>
            <p><?= esc($sesion['fecha'] ?? '') ?> · <?= esc($sesion['detalle'] ?? '') ?></p>
        <?php endforeach; ?>
        <h2>Cuadros</h2>
        <?php foreach ($contrato['cuadros'] as $cuadro): ?>
            <p><?= esc($cuadro['detalle'] ?? '') ?> · Cantidad: <?= esc($cuadro['cantidad'] ?? 0) ?></p>
        <?php endforeach; ?>
        <h2>Alumnos</h2>
        <?php foreach ($alumnos as $alumno): ?>
            <p><?= esc($alumno['nombre_completo']) ?> · <?= esc($alumno['estado']) ?></p>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php if ($puedeEditar): ?>
    <script>
        const appendDetailRow = (containerId, html) => {
            const container = document.getElementById(containerId);
            const index = container.querySelectorAll('.service-row').length;
            const row = document.createElement('div');
            row.className = 'service-row';
            row.innerHTML = html(index);
            container.append(row);
        };
        document.getElementById('add-contract-service').addEventListener('click', () => appendDetailRow('contract-services', (index) => `
            <label>Servicio<input name="servicios[${index}][nombre]" maxlength="120" required></label>
            <label>Precio por estudiante (S/)<input name="servicios[${index}][precio_unitario]" type="number" min="0" step="0.01" value="0" required></label>`));
        document.getElementById('add-contract-session').addEventListener('click', () => appendDetailRow('contract-agenda', (index) => `
            <label>Fecha<input name="agenda[${index}][fecha]" type="date"></label>
            <label>Sesión / detalle<input name="agenda[${index}][detalle]" maxlength="500"></label>`));
        document.getElementById('add-contract-frame').addEventListener('click', () => appendDetailRow('contract-frames', (index) => `
            <label>Especificación<input name="cuadros[${index}][detalle]" maxlength="300"></label>
            <label>Cantidad<input name="cuadros[${index}][cantidad]" type="number" min="1" max="10000" value="1"></label>`));
    </script>
<?php endif; ?>
<?= $this->endSection() ?>
