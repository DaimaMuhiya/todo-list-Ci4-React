<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->get('todos', 'Todos::index');
    $routes->get('todos/(:num)', 'Todos::show/$1');
    $routes->post('todos', 'Todos::create');
    $routes->put('todos/(:num)', 'Todos::update/$1');
    $routes->delete('todos/(:num)', 'Todos::delete/$1');
});
