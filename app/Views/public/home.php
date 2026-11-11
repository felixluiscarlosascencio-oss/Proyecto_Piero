<?= $this->extend('layouts/main') ?>

<?= $this->section('titulo') ?>Inicio<?= $this->endSection() ?>

<?= $this->section('contenido') ?>

<section class="hero">
    <div class="contenedor">
        <h1>Fotos y videos escolares</h1>
        <p>Capturamos cada etapa de tus estudiantes: promociones, actuaciones y recuerdos que duran toda la vida.</p>
        <div style="margin-top: 25px;">
            <a class="boton" href="#cotizador">SOLICITAR COTIZACION</a>
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
                        <?php if (! empty($servicio['etiqueta_destacada'])): ?>
                            <span class="servicio-etiqueta"><?= esc($servicio['etiqueta_destacada']) ?></span>
                        <?php endif; ?>
                        <p><?= esc($servicio['descripcion'] ?: 'Servicio fotográfico para promociones escolares.') ?></p>
                        <p class="servicio-precio">Precio sujeto a cotización personalizada</p>
                        <label class="servicio-seleccion">
                            <input type="checkbox" name="servicios[]" value="<?= esc($servicio['id']) ?>" form="solicitud-cotizacion">
                            <span>Incluir en mi solicitud</span>
                        </label>
                        <button class="servicio-mas" type="button" data-bs-toggle="modal" data-bs-target="#servicio-modal-<?= (int) $servicio['id'] ?>">
                            Ver más <span aria-hidden="true">&rarr;</span>
                        </button>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php foreach ($servicios as $servicio): ?>
                <?php
                $galeria = json_decode($servicio['galeria_json'] ?? '[]', true);
                $galeria = is_array($galeria) ? $galeria : [];
                ?>
                <div class="modal fade servicio-modal" id="servicio-modal-<?= (int) $servicio['id'] ?>" tabindex="-1" aria-labelledby="servicio-modal-titulo-<?= (int) $servicio['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <span class="modal-eyebrow">Piero Producciones</span>
                                    <h2 class="modal-title" id="servicio-modal-titulo-<?= (int) $servicio['id'] ?>"><?= esc($servicio['nombre_item']) ?></h2>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <p><?= nl2br(esc($servicio['detalle_largo'] ?: $servicio['descripcion'] ?: 'Servicio fotográfico para promociones escolares.')) ?></p>
                                <?php if (! empty($servicio['etiqueta_destacada'])): ?>
                                    <p class="servicio-modal-etiqueta"><?= esc($servicio['etiqueta_destacada']) ?></p>
                                <?php endif; ?>
                                <?php if ($galeria !== []): ?>
                                    <div class="servicio-galeria">
                                        <?php foreach ($galeria as $imagen): ?>
                                            <?php if (is_string($imagen) && $imagen !== ''): ?>
                                                <img src="<?= esc(base_url(ltrim($imagen, '/'))) ?>" alt="Acabado de <?= esc($servicio['nombre_item']) ?>" loading="lazy">
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="boton" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="seccion" id="cotizador">
    <div class="contenedor">
        <div class="cotizador-encabezado">
            <span class="seccion-etiqueta">Empecemos a crear recuerdos</span>
            <h2>Solicita una cotización</h2>
            <p>Cuéntanos sobre tu promoción. Nuestro equipo te atenderá por WhatsApp.</p>
        </div>
        <div class="cotizador-layout">
            <form id="solicitud-cotizacion" class="solicitud-form" action="<?= base_url('cotizaciones/solicitar') ?>" method="post">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="persona_encargada">Tu nombre</label>
                        <input class="form-control" id="persona_encargada" name="persona_encargada" type="text" maxlength="150" placeholder="Nombre y apellido de quien escribe" autocomplete="name" required>
                    </div>
                    <div class="form-group">
                        <label for="nombre_colegio">Nombre de la institución</label>
                        <input class="form-control" id="nombre_colegio" name="nombre_colegio" type="text" maxlength="150" placeholder="Ej. Colegio Santa Rosa de San Borja" autocomplete="organization" required>
                    </div>
                    <div class="form-group">
                        <label for="distrito">Distrito</label>
                        <input class="form-control" id="distrito" name="distrito" type="text" maxlength="100" placeholder="Ej. San Borja" required>
                    </div>
                    <div class="form-group">
                        <label for="provincia">Provincia</label>
                        <input class="form-control" id="provincia" name="provincia" type="text" maxlength="100" placeholder="Ej. Lima" required>
                    </div>
                    <div class="form-group">
                        <label for="cantidad_estudiantes">Cantidad de estudiantes</label>
                        <input class="form-control" id="cantidad_estudiantes" name="cantidad_estudiantes" type="number" min="1" max="10000" placeholder="Ej. 24" required>
                    </div>
                    <div class="form-group">
                        <label for="nivel">Nivel</label>
                        <select class="form-control" id="nivel" name="nivel" required>
                            <option value="" selected disabled>Elige el nivel</option>
                            <option value="Inicial">Inicial</option>
                            <option value="Primaria">Primaria</option>
                            <option value="Secundaria">Secundaria</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tipo_servicio">Servicio a elegir</label>
                        <select class="form-control" id="tipo_servicio" name="tipo_servicio" required>
                            <option value="" selected disabled>Elige un servicio</option>
                            <option value="Anuario">Anuario</option>
                            <option value="Cuadros">Cuadros</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="telefono_contacto">Teléfono / WhatsApp</label>
                        <input class="form-control" id="telefono_contacto" name="telefono_contacto" type="tel" maxlength="30" autocomplete="tel" required>
                    </div>
                    <div class="form-group form-group--wide">
                        <label for="observaciones">Observaciones o mensaje adicional <span>(opcional)</span></label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="4" maxlength="1000" placeholder="Fechas que tienen en mente, dónde les gustaría la sesión, cualquier detalle que quieras contarnos"></textarea>
                    </div>
                </div>
                <input class="campo-oculto" name="website" type="text" tabindex="-1" autocomplete="off" aria-hidden="true">
                <?php if ($recaptchaSiteKey !== ''): ?>
                    <div class="g-recaptcha" data-sitekey="<?= esc(trim((string) env('RECAPTCHA_SITE_KEY'))) ?>"></div>
                <?php elseif ($recaptchaMisconfigured): ?>
                    <p class="captcha-aviso" role="status">El formulario está temporalmente no disponible. Inténtalo más tarde.</p>
                <?php endif; ?>
                <button class="boton" type="submit" <?= $recaptchaMisconfigured ? 'disabled' : '' ?>>Enviar por WhatsApp</button>
                <p id="solicitud-mensaje" role="status" aria-live="polite"></p>
            </form>
            <aside class="cotizador-banner">
                <span class="banner-icono" aria-hidden="true"><i class="fa-solid fa-camera-retro"></i></span>
                <p class="banner-etiqueta">Piero Producciones</p>
                <h3>Momentos únicos y recuerdos para siempre.</h3>
                <p>Fotografía y video escolar hechos con dedicación para acompañar cada etapa de su promoción.</p>
                <ul>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Atención personalizada</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i> Paquetes a tu medida</li>
                    <li><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> Respuesta directa por WhatsApp</li>
                </ul>
            </aside>
        </div>
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
        const submitButton = form.querySelector('[type="submit"]');
        const whatsapp = window.open('', '_blank');

        if (!whatsapp) {
            message.textContent = 'Permite las ventanas emergentes para abrir WhatsApp.';
            return;
        }

        submitButton.disabled = true;
        try {
            const formData = new FormData(form);
            if (window.grecaptcha) {
                const captchaToken = window.grecaptcha.getResponse();
                if (captchaToken) formData.set('g-recaptcha-response', captchaToken);
            }

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
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
            submitButton.disabled = false;
        } catch (error) {
            whatsapp.close();
            message.textContent = error.message || 'Ocurrió un error al enviar la solicitud.';
            if (window.grecaptcha) window.grecaptcha.reset();
            submitButton.disabled = false;
        }
    });
</script>

<?= $this->endSection() ?>
