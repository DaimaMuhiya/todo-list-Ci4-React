<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Auth extends BaseConfig
{
    /** Nom du cookie HttpOnly contenant le JWT. */
    public string $cookieName = 'tf_auth';

    /** Secret HS256 — définir `auth.jwtSecret` dans `.env` en production. */
    public string $jwtSecret = '';

    public int $jwtTtlSeconds = 604800;

    /** URL du frontend pour redirection après lien magique (sans slash final). */
    public string $frontendBaseUrl = 'https://todo-list-ci4-react.vercel.app/';

    public function __construct()
    {
        parent::__construct();

        $secret = env('auth.jwtSecret', '');
        $this->jwtSecret = $secret !== '' ? (string) $secret : (string) env('encryption.key', 'change-me-insecure');

        $fe = env('auth.frontendBaseUrl', '');
        if ($fe !== '') {
            $this->frontendBaseUrl = rtrim((string) $fe, '/');
        }
    }
}
