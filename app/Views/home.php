<?= $this->extend('layouts/main') ?>

<?= $this->section('titulo') ?>Inicio<?= $this->endSection() ?>

<?= $this->section('contenido') ?>

<?php helper('contacto'); ?>

<section class="hero">
    <div class="contenedor">
        <h1>Fotos y videos escolares</h1>
        <p>Capturamos cada etapa de tus estudiantes: promociones, actuaciones y recuerdos que duran toda la vida.</p>
        
        <!-- Contenedor con los dos botones -->
        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
            <!-- Botón de WhatsApp (Intacto) -->
            <a class="boton" href="<?= enlace_wsp() ?>" target="_blank" rel="noopener">Solicitar información</a>
            
            <!-- Nuevo botón para ir directo al Cotizador -->
            <a class="boton" href="<?= base_url('cotizaciones/crear') ?>" style="background: #d4af37; color: #000; font-weight: bold;">Crear Cotización</a>
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