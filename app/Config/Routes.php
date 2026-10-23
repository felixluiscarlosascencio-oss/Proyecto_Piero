<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// ==========================================
// Rutas para el Módulo de Autenticación (Login)
// ==========================================
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::processLogin');
$routes->get('logout', 'AuthController::logout');

// ==========================================
// Rutas para el Módulo de Cotizaciones
// ==========================================
$routes->get('cotizaciones', 'CotizacionController::index');                   // Ver listado de cotizaciones
$routes->get('cotizaciones/crear', 'CotizacionController::crear');             // Formulario para crear nueva
$routes->post('cotizaciones/guardar', 'CotizacionController::guardar');          // Procesar y guardar en BD
$routes->get('cotizaciones/editar/(:num)', 'CotizacionController::editar/$1');  // Cargar datos para editar
$routes->post('cotizaciones/actualizar/(:num)', 'CotizacionController::actualizar/$1'); // Guardar cambios editados
$routes->get('cotizaciones/eliminar/(:num)', 'CotizacionController::eliminar/$1'); // Eliminar cotización
$routes->get('cotizaciones/pdf/(:num)', 'CotizacionController::descargarPdf/$1');  // Generar y descargar PDF