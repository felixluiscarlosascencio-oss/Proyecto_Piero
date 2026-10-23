<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cotización - Piero Producciones</title>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Archivo CSS Independiente -->
    <link rel="stylesheet" href="<?= base_url('css/cotizaciones.css') ?>">
</head>
<body>

<div class="container" style="max-width: 600px;">
    <div class="header">
        <span class="badge-top"><i class="fa-solid fa-calculator"></i> NUEVA COTIZACIÓN</span>
        <span style="font-size: 12px; color: #9ca3af;">Piero Producciones</span>
    </div>

    <h1>Armar Cotización Escolar</h1>
    <p class="subtitle">Ingresa los datos del colegio y selecciona los requerimientos de la promoción.</p>

    <!-- FORMULARIO DE CREACIÓN -->
    <form action="<?= base_url('cotizaciones/guardar') ?>" method="POST">

        <!-- Datos Generales del Colegio -->
        <div class="card-box">
            <div style="font-size: 12px; color: #d4af37; margin-bottom: 10px; font-weight: 600; letter-spacing: 0.5px;">1. DATOS DE LA INSTITUCIÓN</div>
            
            <div style="margin-bottom: 12px;">
                <label style="font-size: 13px; color: #9ca3af; display: block; margin-bottom: 4px;">Nombre del Colegio</label>
                <input type="text" name="nombre_colegio" placeholder="Ej. San Agustín, San Juan Bosco..." required style="width: 100%; padding: 10px; background: #0b0b0b; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 14px;">
            </div>

            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label style="font-size: 13px; color: #9ca3af; display: block; margin-bottom: 4px;">Nivel / Grado</label>
                    <select name="grado" style="width: 100%; padding: 10px; background: #0b0b0b; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 14px;">
                        <option value="5to Secundaria">5to de Secundaria</option>
                        <option value="6to Primaria">6to de Primaria</option>
                        <option value="Inicial 5 años">Promoción Inicial (5 años)</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 13px; color: #9ca3af; display: block; margin-bottom: 4px;">Sección</label>
                    <input type="text" name="seccion" placeholder="Ej. 5to 'A', Única..." required style="width: 100%; padding: 10px; background: #0b0b0b; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 14px;">
                </div>
            </div>
        </div>

        <!-- Control de Alumnos y Sesiones -->
        <div class="card-box">
            <div style="font-size: 12px; color: #d4af37; margin-bottom: 10px; font-weight: 600; letter-spacing: 0.5px;">2. CANTIDAD Y PRODUCCIÓN</div>
            
            <div class="students-control" style="margin-bottom: 14px;">
                <div>
                    <div style="font-size: 13px; font-weight: 600;">Total de Alumnos</div>
                    <div style="font-size: 11px; color: #9ca3af;">Estudiantes matriculados</div>
                </div>
                <div class="counter-btns">
                    <button type="button" class="counter-btn" onclick="cambiarAlumnos(-1)">-</button>
                    <span id="alumnos-count">28</span>
                    <input type="hidden" name="cantidad_alumnos" id="input-alumnos" value="28">
                    <button type="button" class="counter-btn" onclick="cambiarAlumnos(1)">+</button>
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label style="font-size: 12px; color: #9ca3af; display: block; margin-bottom: 4px;">Sesiones de Foto/Video</label>
                    <select name="sesiones" style="width: 100%; padding: 8px; background: #0b0b0b; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 13px;">
                        <option value="1">1 Sesión (Estudio/Colegio)</option>
                        <option value="2">2 Sesiones (Colegio + Exteriores)</option>
                        <option value="3">3 Sesiones (Completo + Drone)</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 12px; color: #9ca3af; display: block; margin-bottom: 4px;">Modelo de Cuadro</label>
                    <select name="modelo_cuadro" style="width: 100%; padding: 8px; background: #0b0b0b; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 13px;">
                        <option value="Madera con placa dorada">Madera con placa dorada</option>
                        <option value="Acrílico moderno">Acrílico moderno</option>
                        <option value="Marco clásico esculpido">Marco clásico esculpido</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Módulos y Servicios Opcionales -->
        <div style="font-size: 12px; color: #9ca3af; margin-bottom: 8px; font-weight: 600; letter-spacing: 0.5px;">3. SELECCIONAR SERVICIOS</div>
        
        <div class="card-box">
            <label class="service-item">
                <input type="checkbox" name="servicios[]" class="service-check" value="140" checked onchange="calcularTotal()">
                <div class="service-info">
                    <div class="service-title">Paquete Base Anuario</div>
                    <div class="service-desc">Álbum pasta dura 30x30, 24 páginas laminadas y retrato individual.</div>
                </div>
                <div class="service-price">S/ 140</div>
            </label>
            
            <hr style="border: 0; border-top: 1px solid #2a2a2a; margin: 12px 0;">

            <label class="service-item">
                <input type="checkbox" name="servicios[]" class="service-check" value="35" checked onchange="calcularTotal()">
                <div class="service-info">
                    <div class="service-title">Sesión de Exteriores & Drone</div>
                    <div class="service-desc">Producción en locación urbana y tomas aéreas 4K.</div>
                </div>
                <div class="service-price">S/ 35</div>
            </label>

            <hr style="border: 0; border-top: 1px solid #2a2a2a; margin: 12px 0;">

            <label class="service-item">
                <input type="checkbox" name="servicios[]" class="service-check" value="45" checked onchange="calcularTotal()">
                <div class="service-info">
                    <div class="service-title">Cuadro Retrato de Graduación</div>
                    <div class="service-desc">Retrato decorativo de alta gama con acabado texturizado.</div>
                </div>
                <div class="service-price">S/ 45</div>
            </label>
        </div>

        <!-- Resumen Dinámico y Botón de Guardado -->
        <div class="summary-box">
            <div class="summary-row">
                <div>
                    <div style="font-size: 11px; color: #9ca3af; letter-spacing: 0.5px;">INVERSIÓN POR ALUMNO</div>
                    <div id="precio-alumno" style="font-size: 18px; font-weight: 600; color: #fff;">S/ 220.00 c/u</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 11px; color: #9ca3af; letter-spacing: 0.5px;">TOTAL SECCIÓN</div>
                    <div id="precio-total" class="summary-total">S/ 6,160.00</div>
                </div>
            </div>

            <button type="submit" class="btn-gold">
                <i class="fa-solid fa-floppy-disk"></i> Guardar y Generar Cotización
            </button>
        </div>

    </form>
</div>

<script>
    let alumnos = 28;

    function cambiarAlumnos(cantidad) {
        alumnos += cantidad;
        if (alumnos < 1) alumnos = 1;
        document.getElementById('alumnos-count').innerText = alumnos;
        document.getElementById('input-alumnos').value = alumnos;
        calcularTotal();
    }

    function calcularTotal() {
        const checkboxes = document.querySelectorAll('.service-check');
        let costoUnitario = 0;

        checkboxes.forEach(chk => {
            if (chk.checked) {
                costoUnitario += parseFloat(chk.value);
            }
        });

        let totalSeccion = costoUnitario * alumnos;

        document.getElementById('precio-alumno').innerText = 'S/ ' + costoUnitario.toFixed(2) + ' c/u';
        document.getElementById('precio-total').innerText = 'S/ ' + totalSeccion.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    calcularTotal();
</script>

</body>
</html>