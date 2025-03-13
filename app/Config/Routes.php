<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->options('api/ping', 'PingController::index');
$routes->head('api/ping', 'PingController::index');

$routes->group('api/auth', function ($routes) {
  $routes->post('login', 'AuthController::login');
  $routes->post('register', 'AuthController::register');
});

$routes->group('api', ['filter' => 'auth'], function ($routes) {
  $routes->resource('siswa', ['controller' => 'SiswaController']);
});

$routes->get('api/logs', 'LogController::index');
