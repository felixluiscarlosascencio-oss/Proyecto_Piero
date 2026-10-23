<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

/*
 * --------------------------------------------------------------------------
 * Módulo de Autenticación (Login)
 * --------------------------------------------------------------------------
 */
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::processLogin');
$routes->get('logout', 'AuthController::logout');

/*
 * --------------------------------------------------------------------------
 * Módulo de Cotizaciones
 * --------------------------------------------------------------------------
 */
$routes->get('cotizaciones', 'CotizacionController::index');
$routes->get('cotizaciones/crear', 'CotizacionController::crear');
$routes->post('cotizaciones/guardar', 'CotizacionController::guardar');
$routes->get('cotizaciones/editar/(:num)', 'CotizacionController::editar/$1');
$routes->post('cotizaciones/actualizar/(:num)', 'CotizacionController::actualizar/$1');
$routes->get('cotizaciones/eliminar/(:num)', 'CotizacionController::eliminar/$1');
$routes->get('cotizaciones/pdf/(:num)', 'CotizacionController::descargarPdf/$1');