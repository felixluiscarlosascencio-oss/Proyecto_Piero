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
        <h2>Alumnos</h2>
        <?php foreach ($alumnos as $alumno): ?>
            <p><?= esc($alumno['nombre_completo']) ?> · <?= esc($alumno['estado']) ?></p>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?= $this->endSection() ?>
