<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    // Preflight CORS : sans route OPTIONS, le routeur renvoie 404 avant les filtres (pas d'en-têtes CORS).
    $routes->options('todos', static fn () => service('response')->setStatusCode(204));
    $routes->options('todos/(:num)', static fn () => service('response')->setStatusCode(204));

    $routes->get('todos', 'Todos::index');
    $routes->get('todos/(:num)', 'Todos::show/$1');
    $routes->post('todos', 'Todos::create');
    $routes->put('todos/(:num)', 'Todos::update/$1');
    $routes->delete('todos/(:num)', 'Todos::delete/$1');
});
