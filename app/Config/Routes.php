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

  $routes->resource('transactions', ['controller' => 'TransactionsController']);
  $routes->post('transactions/create-transaction', 'TransactionsController::createTransaction');
  $routes->post('transactions/get-receipt', 'TransactionsController::get_receipt');

  $routes->resource('products', ['controller' => 'ProductsController']);
  $routes->resource('categories', ['controller' => 'CategoriesController']);
  $routes->resource('subcategories', ['controller' => 'SubCategoriesController']);
  $routes->resource('branch', ['controller' => 'BranchController']);


  $routes->get('transaction-details/transaction/(:num)', 'TransactionsDetailsController::showByTransactionId/$1');
  $routes->resource('transaction-details', ['controller' => 'TransactionsDetailsController']);


  // Reports endpoint
  $routes->post('reports/sales', 'ReportsController::sales');
  $routes->post('reports/charts', 'ReportsController::charts');
});

$routes->get('api/logs', 'LogController::index');
