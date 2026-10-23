<?= $this->extend('layouts/main') ?>

<?= $this->section('titulo') ?>Inicio<?= $this->endSection() ?>

<?= $this->section('contenido') ?>

<?php helper('contacto'); ?>

<section class="hero">
    <div class="contenedor">
        <h1>Fotos y videos escolares</h1>
        <p>Capturamos cada etapa de tus estudiantes: promociones, actuaciones y recuerdos que duran toda la vida.</p>
        
        <!-- Botón único y destacado en el Hero -->
        <div style="margin-top: 25px;">
            <a class="boton" href="<?= enlace_wsp() ?>" target="_blank" rel="noopener">
                Solicitar información
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

<?= $this->endSection() ?>