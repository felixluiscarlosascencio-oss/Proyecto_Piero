<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::processLogin');
$routes->post('logout', 'AuthController::logout');
$routes->post('cotizaciones/solicitar', 'SolicitudCotizacionController::crear');

$routes->group('admin', ['filter' => 'auth'], static function ($routes): void {
    $routes->get('/', 'DashboardController::index');
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('reportes', 'DashboardController::reportes');
    $routes->get('buscar', 'DashboardController::buscar');
    $routes->get('cotizaciones', 'CotizacionController::index');
    $routes->get('cotizaciones/vigentes', 'CotizacionController::vigentes');
    $routes->get('cotizaciones/nueva', 'CotizacionController::nueva', ['filter' => 'auth:admin']);
    $routes->get('cotizaciones/(:num)/duplicar', 'CotizacionController::duplicar/$1', ['filter' => 'auth:admin']);
    $routes->post('cotizaciones/crear', 'CotizacionController::crear', ['filter' => 'auth:admin']);
    $routes->get('cotizaciones/(:num)/ver', 'CotizacionController::ver/$1');
    $routes->get('cotizaciones/(:num)', 'CotizacionController::ver/$1');
    $routes->post('cotizaciones/(:num)/eliminar', 'CotizacionController::eliminar/$1');
    $routes->post('cotizaciones/(:num)/precios', 'CotizacionController::actualizarPrecios/$1', ['filter' => 'auth:admin']);
    $routes->post('cotizaciones/(:num)/activar', 'CotizacionController::activar/$1', ['filter' => 'auth:admin']);
    $routes->post('cotizaciones/(:num)/contrato', 'CotizacionController::pasarAContrato/$1', ['filter' => 'auth:admin']);
    $routes->post('cotizaciones/(:num)/pdf', 'CotizacionController::descargarPdf/$1', ['filter' => 'auth:admin']);

    $routes->get('usuarios', 'UsuarioAdminController::index', ['filter' => 'auth:admin']);
    $routes->post('usuarios', 'UsuarioAdminController::crear', ['filter' => 'auth:admin']);
    $routes->post('usuarios/(:num)', 'UsuarioAdminController::actualizar/$1', ['filter' => 'auth:admin']);
    $routes->post('usuarios/(:num)/eliminar', 'UsuarioAdminController::eliminar/$1', ['filter' => 'auth:admin']);

    $routes->get('solicitudes', 'SolicitudAdminController::index', ['filter' => 'auth:admin']);
    $routes->post('solicitudes/(:num)/aprobar', 'SolicitudAdminController::resolver/$1/aprobar', ['filter' => 'auth:admin']);
    $routes->post('solicitudes/(:num)/denegar', 'SolicitudAdminController::resolver/$1/denegar', ['filter' => 'auth:admin']);

    $routes->get('servicios', 'ServicioAdminController::index', ['filter' => 'auth:admin']);
    $routes->get('servicios/nuevo', 'ServicioAdminController::nuevo', ['filter' => 'auth:admin']);
    $routes->post('servicios', 'ServicioAdminController::crear', ['filter' => 'auth:admin']);
    $routes->get('servicios/(:num)/editar', 'ServicioAdminController::editar/$1', ['filter' => 'auth:admin']);
    $routes->post('servicios/(:num)', 'ServicioAdminController::actualizar/$1', ['filter' => 'auth:admin']);
    $routes->post('servicios/(:num)/eliminar', 'ServicioAdminController::eliminar/$1', ['filter' => 'auth:admin']);

    $routes->get('contratos', 'ContratoController::index');
    $routes->get('contratos/(:num)', 'ContratoController::editar/$1');
    $routes->post('contratos/(:num)', 'ContratoController::actualizar/$1');
    $routes->post('contratos/(:num)/ajustes', 'ContratoController::crearAjuste/$1');
});