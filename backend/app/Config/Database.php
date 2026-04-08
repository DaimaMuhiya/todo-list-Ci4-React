<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 *
 * En local : les valeurs viennent de `.env` (DotEnv).
 * Sur Render : pas de `.env` dans l’image → variables dans le dashboard.
 * Ordre : {@see BaseConfig} fusionne d’abord `database.default.*`, puis
 * {@see applyLibpqFromEnvironment()} et {@see applyDatabaseUrlFromEnvironment()}
 * (une `DATABASE_URL` valide a priorité sur les champs individuels).
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * @var array<string, mixed>
     */
    public array $default = [
        'DSN'        => '',
        'hostname'   => 'localhost',
        'username'   => 'postgres',
        'password'   => '',
        'database'   => 'taskflow',
        'schema'     => 'public',
        'DBDriver'   => 'Postgre',
        'DBPrefix'   => '',
        'pConnect'   => false,
        'DBDebug'    => true,
        'charset'    => 'utf8',
        'swapPre'    => '',
        'failover'   => [],
        'port'       => 5432,
        // Vide en local ; sur Render avec Postgres managé : « require » (voir DATABASE_URL ou database.default.sslmode)
        'sslmode'    => '',
        'dateFormat' => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    //    /**
    //     * Sample database connection for SQLite3.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'database'    => 'database.db',
    //        'DBDriver'    => 'SQLite3',
    //        'DBPrefix'    => '',
    //        'DBDebug'     => true,
    //        'swapPre'     => '',
    //        'failover'    => [],
    //        'foreignKeys' => true,
    //        'busyTimeout' => 1000,
    //        'synchronous' => null,
    //        'dateFormat'  => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for Postgre.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => '',
    //        'hostname'   => 'localhost',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'database'   => 'ci4',
    //        'schema'     => 'public',
    //        'DBDriver'   => 'Postgre',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'utf8',
    //        'swapPre'    => '',
    //        'failover'   => [],
    //        'port'       => 5432,
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for SQLSRV.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => '',
    //        'hostname'   => 'localhost',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'database'   => 'ci4',
    //        'schema'     => 'dbo',
    //        'DBDriver'   => 'SQLSRV',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'utf8',
    //        'swapPre'    => '',
    //        'encrypt'    => false,
    //        'failover'   => [],
    //        'port'       => 1433,
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for OCI8.
    //     *
    //     * You may need the following environment variables:
    //     *   NLS_LANG                = 'AMERICAN_AMERICA.UTF8'
    //     *   NLS_DATE_FORMAT         = 'YYYY-MM-DD HH24:MI:SS'
    //     *   NLS_TIMESTAMP_FORMAT    = 'YYYY-MM-DD HH24:MI:SS'
    //     *   NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS'
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => 'localhost:1521/XEPDB1',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'DBDriver'   => 'OCI8',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'AL32UTF8',
    //        'swapPre'    => '',
    //        'failover'   => [],
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    /**
     * This database connection is used when running PHPUnit database tests.
     *
     * @var array<string, mixed>
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => '',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => true,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
        'synchronous' => null,
        'dateFormat'  => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        // Après la fusion .env / variables (BaseConfig), sinon des clés comme
        // database.default.hostname=localhost peuvent écraser une URL déjà parsée.
        $this->applyLibpqFromEnvironment();
        $this->applyDatabaseUrlFromEnvironment();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }

    /**
     * Lecture robuste des variables injectées par le PaaS (Apache ne remplit pas
     * toujours getenv() comme CLI — $_SERVER est souvent peuplé).
     */
    private function readEnvNonEmpty(string $key): string
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        if (isset($_ENV[$key]) && is_string($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        return '';
    }

    /**
     * Variables d’environnement standard libpq (optionnel, ex. scripts ou images custom).
     * Surchargée par {@see applyDatabaseUrlFromEnvironment()} si `DATABASE_URL` est défini.
     */
    private function applyLibpqFromEnvironment(): void
    {
        $host = $this->readEnvNonEmpty('PGHOST');
        if ($host === '') {
            return;
        }

        $this->default['DBDriver'] = 'Postgre';
        $this->default['hostname'] = $host;

        $port = $this->readEnvNonEmpty('PGPORT');
        $this->default['port'] = $port !== '' ? (int) $port : 5432;

        $user = $this->readEnvNonEmpty('PGUSER');
        if ($user !== '') {
            $this->default['username'] = $user;
        }

        $password = $this->readEnvNonEmpty('PGPASSWORD');
        if ($password !== '') {
            $this->default['password'] = $password;
        }

        $database = $this->readEnvNonEmpty('PGDATABASE');
        if ($database !== '') {
            $this->default['database'] = $database;
        }

        $sslmode = $this->readEnvNonEmpty('PGSSLMODE');
        if ($sslmode !== '') {
            $this->default['sslmode'] = $sslmode;
        }
    }

    /**
     * Render (et autres PaaS) exposent souvent Postgres uniquement via DATABASE_URL.
     * Sans ceci, le conteneur garde hostname=localhost et la connexion échoue (HTTP 500).
     *
     * Sur Render : lier l’instance PostgreSQL au Web Service pour injecter `DATABASE_URL`.
     */
    private function applyDatabaseUrlFromEnvironment(): void
    {
        $url = $this->normalizeDatabaseUrl($this->readEnvNonEmpty('DATABASE_URL'));
        if ($url === '') {
            return;
        }

        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'])) {
            return;
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['postgres', 'postgresql'], true)) {
            return;
        }

        $this->default['DBDriver'] = 'Postgre';
        $this->default['hostname'] = (string) ($parts['host'] ?? '');
        $this->default['port'] = isset($parts['port']) ? (int) $parts['port'] : 5432;
        $this->default['username'] = isset($parts['user']) ? rawurldecode((string) $parts['user']) : '';
        $this->default['password'] = isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : '';
        $this->default['database'] = isset($parts['path']) ? rawurldecode(ltrim((string) $parts['path'], '/')) : '';
        $this->default['sslmode'] = 'require';

        if (! empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            if (! empty($query['sslmode'])) {
                $this->default['sslmode'] = (string) $query['sslmode'];
            }
        }
    }

    /**
     * Sans ceci, une valeur collée dans Render avec des guillemets littéraux
     * (`"postgresql://..."`) fait échouer parse_url : l’URL est ignorée et l’hôte
     * reste celui du fichier (ex. localhost).
     */
    private function normalizeDatabaseUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (
            ($url[0] === '"' && str_ends_with($url, '"'))
            || ($url[0] === "'" && str_ends_with($url, "'"))
        ) {
            $url = substr($url, 1, -1);
        }

        return trim($url);
    }
}
