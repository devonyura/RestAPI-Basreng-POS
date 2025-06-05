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
  // Reports endpoint
  // $routes->post('reports/sales', 'ReportsController::sales');
  // $routes->post('reports/sales', 'ReportsController::sales');
  // $routes->post('reports/charts', 'ReportsController::charts');



  // Report
  $routes->get('report/getDetailReport/(:any)', 'ReportController::getDetailReport/$1');
  $routes->get('report/getAllReports', 'ReportController::getAllReports');
  $routes->get('report/getTransactionsReport', 'ReportController::getTransactionsReport');
  $routes->get('report/getTransactionsReport/(:num)', 'ReportController::getTransactionsReport/$1');
  // ===============================
  $routes->get('report/summary', 'ReportController::summary');
  $routes->get('report/getBranchReport', 'ReportController::getBranchReport');
  $routes->get('report/top-selling', 'ReportController::topSelling');

  // $routes->get('chart/getProductSellsReport', 'ChartController::getProductSellsReport');
  // $routes->get('chart/getProductSellsReport/(:num)', 'ChartController::getProductSellsReport/$1');

  // $routes->get('chart/getBranchReport', 'ChartController::getBranchReport');
  // $routes->get('chart/getBranchReport/(:num)', 'ChartController::getBranchReport/$1');
  // $routes->get('chart/branch-performance', 'ChartController::branchPerformance');
  // $routes->get('chart/category-summary', 'ChartController::categorySummary');

  // users data
  $routes->resource('users', ['controller' => 'UsersController']);
  $routes->post('users/reset-password', 'UsersController::resetPassword');

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
});

$routes->get('api/logs', 'LogController::index');
