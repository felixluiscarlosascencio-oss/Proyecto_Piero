<?= $this->extend('layouts/main') ?>

<?= $this->section('titulo') ?>Inicio<?= $this->endSection() ?>

<?= $this->section('contenido') ?>

<section class="hero">
    <div class="contenedor">
        <h1>Fotos y videos escolares</h1>
        <p>Capturamos cada etapa de tus estudiantes: promociones, actuaciones y recuerdos que duran toda la vida.</p>
        
        <!-- Botón único y destacado en el Hero -->
        <div style="margin-top: 25px;">
            <a class="boton" href="#cotizador">
                Solicitar Cotización
            </a>
        </div>
    </div>
</section>

<section class="seccion" id="servicios">
    <div class="contenedor">
        <h2>Nuestros servicios</h2>
        <div class="tarjetas">
            <article class="tarjeta">
                <h3>Fotografía escolar</h3>
                <p>Fotos individuales y grupales de cada aula.</p>
            </article>
            <article class="tarjeta">
                <h3>Video de eventos</h3>
                <p>Graduaciones, clausuras y actividades del colegio.</p>
            </article>
            <article class="tarjeta">
                <h3>Álbumes y recuerdos</h3>
                <p>Álbumes impresos y entrega digital para las familias.</p>
            </article>
        </div>
    </div>
</section>

<section class="seccion" id="cotizador">
    <div class="contenedor">
        <h2>Solicita una cotización</h2>
        <p>Déjanos los datos básicos y enviaremos tu solicitud a nuestro equipo por WhatsApp.</p>
        <form id="solicitud-cotizacion" class="solicitud-form" action="<?= base_url('cotizaciones/solicitar') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="nombre_colegio">Nombre de la institución</label>
                <input id="nombre_colegio" name="nombre_colegio" type="text" maxlength="150" required>
            </div>
            <div class="form-group">
                <label for="cantidad_estudiantes">Cantidad de estudiantes</label>
                <input id="cantidad_estudiantes" name="cantidad_estudiantes" type="number" min="1" max="10000" required>
            </div>
            <fieldset>
                <legend> Servicios  </legend>
                <label><input type="checkbox" name="servicios[]" value="Paquete Base Anuario"> Paquete Base Anuario</label>
                <label><input type="checkbox" name="servicios[]" value="Sesión de Exteriores y Drone"> Sesión de Exteriores y Drone</label>
                <label><input type="checkbox" name="servicios[]" value="Cuadro Retrato de Graduación"> Cuadro Retrato de Graduación</label>
                <label><input type="checkbox" name="servicios[]" value="Cuadro Retrato de Graduación"> Cuadro Giratorio de Graducacion</label>
                <label><input type="checkbox" name="servicios[]" value="Cuadro Retrato de Graduación"> Cuadro de Graduación</label>

            </fieldset>
            <button class="boton" type="submit">Enviar solicitud por WhatsApp</button>
            <p id="solicitud-mensaje" role="status" aria-live="polite"></p>
        </form>
    </div>
</section>

<script>
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

            if (!response.ok) {
                throw new Error(result.message || 'No se pudo registrar la solicitud.');
            }

            whatsapp.opener = null;
            whatsapp.location.href = result.whatsapp_url;
            message.textContent = 'Solicitud registrada. Completa el envío del mensaje en WhatsApp.';
            form.reset();
        } catch (error) {
            whatsapp.close();
            message.textContent = error.message || 'Ocurrió un error al enviar la solicitud.';
        }
    });
</script>

<?= $this->endSection() ?>