<?= $this->extend('layouts/main') ?>

<?= $this->section('titulo') ?>Inicio<?= $this->endSection() ?>

<?= $this->section('contenido') ?>

<section class="hero">
    <div class="contenedor">
        <h1>Fotos y videos escolares</h1>
        <p>Capturamos cada etapa de tus estudiantes: promociones, actuaciones y recuerdos que duran toda la vida.</p>
        <div style="margin-top: 25px;">
            <a class="boton" href="#cotizador">Solicitar Cotización</a>
        </div>
    </div>
</section>

<section class="seccion" id="servicios">
    <div class="contenedor">
        <h2>Nuestros servicios</h2>
        <?php if ($servicios === []): ?>
            <p>Estamos actualizando nuestro catálogo de servicios. Contáctanos para recibir información.</p>
        <?php else: ?>
            <div class="tarjetas">
                <?php foreach ($servicios as $servicio): ?>
                    <?php
                    $galeria = json_decode($servicio['galeria_json'] ?? '[]', true);
                    $galeria = is_array($galeria) ? $galeria : [];
                    ?>
                    <article class="tarjeta servicio-card">
                        <h3><?= esc($servicio['nombre_item']) ?></h3>
                        <p><?= esc($servicio['descripcion'] ?: 'Servicio fotográfico para promociones escolares.') ?></p>
                        <p class="servicio-precio">
                            <?= (float) $servicio['precio_unitario'] > 0
                                ? 'S/ ' . number_format((float) $servicio['precio_unitario'], 2) . ' por alumno'
                                : 'Precio a consultar' ?>
                        </p>
                        <label class="servicio-seleccion">
                            <input type="checkbox" name="servicios[]" value="<?= esc($servicio['id']) ?>" form="solicitud-cotizacion">
                            <span>Incluir en mi solicitud</span>
                        </label>
                        <details class="servicio-detalle">
                            <summary>Ver más</summary>
                            <p><?= esc($servicio['detalle_largo'] ?: $servicio['descripcion']) ?></p>
                            <?php if ($galeria !== []): ?>
                                <div class="servicio-galeria">
                                    <?php foreach ($galeria as $imagen): ?>
                                        <?php if (is_string($imagen) && $imagen !== ''): ?>
                                            <img src="<?= esc(base_url(ltrim($imagen, '/'))) ?>" alt="Acabado de <?= esc($servicio['nombre_item']) ?>" loading="lazy">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </details>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="seccion" id="cotizador">
    <div class="contenedor">
        <h2>Solicita una cotización</h2>
        <p>Completa tus datos y nuestro equipo atenderá tu solicitud por WhatsApp.</p>
        <form id="solicitud-cotizacion" class="solicitud-form" action="<?= base_url('cotizaciones/solicitar') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="nombre_colegio">Nombre de la institución</label>
                <input class="form-control" id="nombre_colegio" name="nombre_colegio" type="text" maxlength="150" autocomplete="organization" required>
            </div>
            <div class="form-group">
                <label for="nombre_seccion">Nombre de la promoción o sección <span>(opcional)</span></label>
                <input class="form-control" id="nombre_seccion" name="nombre_seccion" type="text" maxlength="120">
            </div>
            <div class="form-group">
                <label for="cantidad_estudiantes">Cantidad de alumnos</label>
                <div class="contador-alumnos">
                    <button type="button" aria-label="Disminuir cantidad" data-step="-1">−</button>
                    <input class="form-control" id="cantidad_estudiantes" name="cantidad_estudiantes" type="number" min="1" max="10000" value="1" required>
                    <button type="button" aria-label="Aumentar cantidad" data-step="1">+</button>
                </div>
            </div>
            <div class="form-group">
                <label for="persona_encargada">Persona encargada o tesorera</label>
                <input class="form-control" id="persona_encargada" name="persona_encargada" type="text" maxlength="150" autocomplete="name" required>
            </div>
            <div class="form-group">
                <label for="telefono_contacto">Teléfono / WhatsApp de contacto</label>
                <input class="form-control" id="telefono_contacto" name="telefono_contacto" type="tel" maxlength="30" autocomplete="tel" required>
            </div>
            <input class="campo-oculto" name="website" type="text" tabindex="-1" autocomplete="off" aria-hidden="true">
            <?php if ($recaptchaSiteKey !== ''): ?>
                <div class="g-recaptcha" data-sitekey="<?= esc($recaptchaSiteKey) ?>"></div>
            <?php elseif ($recaptchaMisconfigured): ?>
                <p class="captcha-aviso" role="status">El formulario está temporalmente no disponible. Inténtalo más tarde.</p>
            <?php endif; ?>
            <button class="boton" type="submit" <?= $recaptchaMisconfigured ? 'disabled' : '' ?>>Enviar solicitud por WhatsApp</button>
            <p id="solicitud-mensaje" role="status" aria-live="polite"></p>
        </form>
    </div>
</section>

<?php if ($recaptchaSiteKey !== ''): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<script>
    const cantidadAlumnos = document.getElementById('cantidad_estudiantes');
    document.querySelectorAll('[data-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const cantidad = Number(cantidadAlumnos.value) || 1;
            cantidadAlumnos.value = Math.min(10000, Math.max(1, cantidad + Number(button.dataset.step)));
        });
    });

    document.getElementById('solicitud-cotizacion').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const message = document.getElementById('solicitud-mensaje');
        const whatsapp = window.open('', '_blank');

        if (!whatsapp) {
            message.textContent = 'Permite las ventanas emergentes para abrir WhatsApp.';
            return;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();
            const csrfField = form.querySelector('input[type="hidden"]');
            if (csrfField && result.csrf_hash) csrfField.value = result.csrf_hash;

            if (!response.ok) {
                throw new Error(result.message || 'No se pudo registrar la solicitud.');
            }

            whatsapp.opener = null;
            whatsapp.location.href = result.whatsapp_url;
            message.textContent = 'Solicitud registrada. Completa el envío del mensaje en WhatsApp.';
            form.reset();
            if (csrfField && result.csrf_hash) csrfField.value = result.csrf_hash;
            if (window.grecaptcha) window.grecaptcha.reset();
        } catch (error) {
            whatsapp.close();
            message.textContent = error.message || 'Ocurrió un error al enviar la solicitud.';
        }
    });
</script>

<?= $this->endSection() ?>
