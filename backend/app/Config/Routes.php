<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $opts = static fn () => service('response')->setStatusCode(204);

    foreach (
        [
            'todos',
            'todos/(:num)',
            'sections',
            'sections/(:num)',
            'auth/register',
            'auth/login',
            'auth/logout',
            'auth/account',
            'auth/me',
            'auth/magic',
            'auth/password-reset/request',
            'auth/password-reset/confirm',
            'auth/password-reset/complete',
            'admin/users',
            'admin/users/(:num)',
        ] as $pattern
    ) {
        $routes->options($pattern, $opts);
    }

    $routes->post('auth/register', 'Auth::register');
    $routes->post('auth/login', 'Auth::login');
    $routes->get('auth/magic', 'Auth::magic');
    $routes->post('auth/password-reset/request', 'Auth::passwordResetRequest');
    $routes->post('auth/password-reset/confirm', 'Auth::passwordResetConfirm');
    $routes->post('auth/password-reset/complete', 'Auth::passwordResetComplete');

    $routes->group('', ['filter' => 'jwtauth'], static function ($routes) {
        $routes->post('auth/logout', 'Auth::logout');
        $routes->delete('auth/account', 'Auth::deleteAccount');
        $routes->get('auth/me', 'Auth::me');

        $routes->get('sections', 'Sections::index');
        $routes->post('sections', 'Sections::create');
        $routes->delete('sections/(:num)', 'Sections::delete/$1');

        $routes->get('todos', 'Todos::index');
        $routes->get('todos/(:num)', 'Todos::show/$1');
        $routes->post('todos', 'Todos::create');
        $routes->put('todos/(:num)', 'Todos::update/$1');
        $routes->delete('todos/(:num)', 'Todos::delete/$1');

        $routes->group('admin', ['filter' => 'admin'], static function ($routes) {
            $routes->get('users', 'AdminUsers::index');
            $routes->patch('users/(:num)', 'AdminUsers::update/$1');
            $routes->delete('users/(:num)', 'AdminUsers::delete/$1');
        });
    });
});
