<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cotización - Piero Producciones</title>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Independiente -->
    <link rel="stylesheet" href="<?= base_url('css/cotizaciones.css') ?>">
</head>
<body>

<div class="cotizador-container">
    
    <div class="header-cotizacion">
        <div>
            <span class="badge-top"><i class="fa-solid fa-calculator"></i> NUEVA COTIZACIÓN</span>
            <h1 style="font-size: 26px; margin-top: 8px;">Armar Cotización Escolar</h1>
            <p style="font-size: 13px; color: #888;">Ingresa los datos del colegio y selecciona los requerimientos de la promoción.</p>
        </div>
        <div style="font-size: 13px; color: #d4af37; font-weight: 600;">
            Piero Producciones
        </div>
    </div>

    <form action="<?= base_url('cotizaciones/guardar') ?>" method="POST">
        
        <!-- GRID DE 2 COLUMNAS -->
        <div class="grid-cotizador">
            
            <!-- COLUMNA IZQUIERDA: FORMULARIO -->
            <div class="col-main">
                
                <!-- 1. DATOS DE LA INSTITUCIÓN -->
                <div class="card-box">
                    <div class="card-title">1. Datos de la Institución</div>
                    
                    <div class="form-group">
                        <label>Nombre del Colegio</label>
                        <input type="text" name="nombre_colegio" class="input-control" placeholder="Ej. San Agustín, San Juan Bosco..." required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nivel / Grado</label>
                            <select name="grado" class="input-control">
                                <option value="5to Secundaria">5to de Secundaria</option>
                                <option value="6to Primaria">6to de Primaria</option>
                                <option value="Inicial 5 años">Promoción Inicial (5 años)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Sección</label>
                            <input type="text" name="seccion" class="input-control" placeholder="Ej. 5to 'A', Única..." required>
                        </div>
                    </div>
                </div>

                <!-- 2. CANTIDAD Y PRODUCCIÓN -->
                <div class="card-box">
                    <div class="card-title">2. Cantidad y Producción</div>
                    
                    <div class="students-control" style="margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 14px; font-weight: 600;">Total de Alumnos</div>
                            <div style="font-size: 11px; color: #888;">Estudiantes matriculados</div>
                        </div>
                        <div class="counter-btns">
                            <button type="button" class="counter-btn" onclick="cambiarAlumnos(-1)">-</button>
                            <span id="alumnos-count">28</span>
                            <input type="hidden" name="cantidad_alumnos" id="input-alumnos" value="28">
                            <button type="button" class="counter-btn" onclick="cambiarAlumnos(1)">+</button>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Sesiones de Foto/Video</label>
                            <select name="sesiones" class="input-control">
                                <option value="1">1 Sesión (Estudio/Colegio)</option>
                                <option value="2">2 Sesiones (Colegio + Exteriores)</option>
                                <option value="3">3 Sesiones (Completo + Drone)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Modelo de Cuadro</label>
                            <select name="modelo_cuadro" class="input-control">
                                <option value="Madera con placa dorada">Madera con placa dorada</option>
                                <option value="Acrílico moderno">Acrílico moderno</option>
                                <option value="Marco clásico esculpido">Marco clásico esculpido</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. SELECCIONAR SERVICIOS -->
                <div class="card-box">
                    <div class="card-title">3. Seleccionar Servicios</div>
                    
                    <label class="service-item">
                        <input type="checkbox" name="servicios[]" class="service-check" value="140" checked onchange="calcularTotal()">
                        <div class="service-info">
                            <div class="service-title">Paquete Base Anuario</div>
                            <div class="service-desc">Álbum pasta dura 30x30, 24 páginas laminadas y retrato individual.</div>
                        </div>
                        <div class="service-price">S/ 140</div>
                    </label>
                    
                    <hr style="border: 0; border-top: 1px solid #222; margin: 12px 0;">

                    <label class="service-item">
                        <input type="checkbox" name="servicios[]" class="service-check" value="35" checked onchange="calcularTotal()">
                        <div class="service-info">
                            <div class="service-title">Sesión de Exteriores & Drone</div>
                            <div class="service-desc">Producción en locación urbana y tomas aéreas 4K.</div>
                        </div>
                        <div class="service-price">S/ 35</div>
                    </label>

                    <hr style="border: 0; border-top: 1px solid #222; margin: 12px 0;">

                    <label class="service-item">
                        <input type="checkbox" name="servicios[]" class="service-check" value="45" onchange="calcularTotal()">
                        <div class="service-info">
                            <div class="service-title">Cuadro Retrato de Graduación</div>
                            <div class="service-desc">Retrato decorativo de alta gama con acabado texturizado.</div>
                        </div>
                        <div class="service-price">S/ 45</div>
                    </label>
                </div>

            </div>

            <!-- COLUMNA DERECHA: RESUMEN LATERAL (STICKY) -->
            <div class="col-sidebar">
                <div class="summary-card">
                    <div class="summary-item">
                        <div class="summary-label">Inversión por Alumno</div>
                        <div id="precio-alumno" class="summary-value">S/ 175.00 c/u</div>
                    </div>

                    <hr style="border: 0; border-top: 1px solid #333; margin: 15px 0;">

                    <div class="summary-item">
                        <div class="summary-label">Total estimado sección</div>
                        <div id="precio-total" class="summary-total">S/ 4,900.00</div>
                    </div>

                    <button type="submit" class="btn-gold">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Cotización
                    </button>
                </div>
            </div>

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