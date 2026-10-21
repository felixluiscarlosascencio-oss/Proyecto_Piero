<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// Rutas para el Módulo de Autenticación (Login)
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::processLogin');
$routes->get('logout', 'AuthController::logout');

// ==========================================
// Rutas para el Módulo de Cotizaciones
// ==========================================
$routes->get('cotizaciones', 'CotizacionController::index');
$routes->get('cotizaciones/crear', 'CotizacionController::crear');
$routes->post('cotizaciones/guardar', 'CotizacionController::guardar');