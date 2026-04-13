<?php

namespace App\Filters;

use App\Libraries\AuthToken;
use App\Libraries\CurrentUser;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JwtAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        CurrentUser::clear();

        $config   = config('Auth');
        $rawToken = $request->getCookie($config->cookieName);

        if ($rawToken === null || $rawToken === '') {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'Authentification requise.']);
        }

        $payload = AuthToken::parse($rawToken, $config->jwtSecret);

        if ($payload === null) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'Session invalide ou expiree.']);
        }

        CurrentUser::set($payload['sub'], $payload['role']);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
