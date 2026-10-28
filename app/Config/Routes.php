<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::processLogin');
$routes->post('logout', 'AuthController::logout');
$routes->post('cotizaciones/solicitar', 'SolicitudCotizacionController::crear');

$routes->group('admin', ['filter' => 'auth'], static function ($routes): void {
    $routes->get('cotizaciones', 'CotizacionController::index');
    $routes->get('cotizaciones/(:num)', 'CotizacionController::editar/$1');
    $routes->post('cotizaciones/(:num)', 'CotizacionController::actualizar/$1');
    $routes->post('cotizaciones/(:num)/aprobar', 'CotizacionController::aprobar/$1');
    $routes->post('cotizaciones/(:num)/pdf', 'CotizacionController::descargarPdf/$1');
    $routes->post('cotizaciones/(:num)/confirmar', 'CotizacionController::confirmar/$1');
});