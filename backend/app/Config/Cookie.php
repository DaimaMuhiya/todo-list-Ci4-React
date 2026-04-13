<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use DateTimeInterface;

class Cookie extends BaseConfig
{
    public function __construct()
    {
        parent::__construct();

        // Cross-origin SPA (ex. Vercel) + API (ex. Render) : sans SameSite=None
        // + Secure, le navigateur n’envoie pas le cookie sur fetch(credentials).
        //
        // Défaut « cross-site » : Vercel + API Render (cookie envoyé sur fetch vers Onrender).
        // - CI_ENVIRONMENT=production, ou
        // - variable RENDER (hébergement Render), au cas où CI_ENVIRONMENT serait oublié.
        $onRenderHost = in_array(
            strtolower((string) getenv('RENDER')),
            ['1', 'true', 'yes'],
            true,
        );
        if (ENVIRONMENT === 'production' || $onRenderHost) {
            $this->samesite = 'None';
            $this->secure   = true;
        }

        $ss = $this->readEnvString([
            'cookie.samesite',
            'cookie_samesite',
            'COOKIE_SAMESITE',
        ]);
        if ($ss !== null && $ss !== '') {
            $norm = strtolower(trim($ss));
            $this->samesite = match ($norm) {
                'none'   => 'None',
                'strict' => 'Strict',
                'lax'    => 'Lax',
                default  => $this->samesite,
            };
        }

        $sec = $this->readEnvString(['cookie.secure', 'cookie_secure', 'COOKIE_SECURE']);
        if ($sec !== null && $sec !== '') {
            $this->secure = filter_var($sec, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->samesite === 'None' && ! $this->secure) {
            $this->secure = true;
        }
    }

    /**
     * @param list<string> $keys
     */
    private function readEnvString(array $keys): ?string
    {
        foreach ($keys as $key) {
            $v = env($key);
            if ($v !== null && $v !== false && $v !== '') {
                return (string) $v;
            }
            $g = getenv($key);
            if ($g !== false && $g !== '') {
                return (string) $g;
            }
        }

        return null;
    }

    /**
     * --------------------------------------------------------------------------
     * Cookie Prefix
     * --------------------------------------------------------------------------
     *
     * Set a cookie name prefix if you need to avoid collisions.
     */
    public string $prefix = '';

    /**
     * --------------------------------------------------------------------------
     * Cookie Expires Timestamp
     * --------------------------------------------------------------------------
     *
     * Default expires timestamp for cookies. Setting this to `0` will mean the
     * cookie will not have the `Expires` attribute and will behave as a session
     * cookie.
     *
     * @var DateTimeInterface|int|string
     */
    public $expires = 0;

    /**
     * --------------------------------------------------------------------------
     * Cookie Path
     * --------------------------------------------------------------------------
     *
     * Typically will be a forward slash.
     */
    public string $path = '/';

    /**
     * --------------------------------------------------------------------------
     * Cookie Domain
     * --------------------------------------------------------------------------
     *
     * Set to `.your-domain.com` for site-wide cookies.
     */
    public string $domain = '';

    /**
     * --------------------------------------------------------------------------
     * Cookie Secure
     * --------------------------------------------------------------------------
     *
     * Cookie will only be set if a secure HTTPS connection exists.
     */
    public bool $secure = false;

    /**
     * --------------------------------------------------------------------------
     * Cookie HTTPOnly
     * --------------------------------------------------------------------------
     *
     * Cookie will only be accessible via HTTP(S) (no JavaScript).
     */
    public bool $httponly = true;

    /**
     * --------------------------------------------------------------------------
     * Cookie SameSite
     * --------------------------------------------------------------------------
     *
     * Configure cookie SameSite setting. Allowed values are:
     * - None
     * - Lax
     * - Strict
     * - ''
     *
     * Alternatively, you can use the constant names:
     * - `Cookie::SAMESITE_NONE`
     * - `Cookie::SAMESITE_LAX`
     * - `Cookie::SAMESITE_STRICT`
     *
     * Defaults to `Lax` for compatibility with modern browsers. Setting `''`
     * (empty string) means default SameSite attribute set by browsers (`Lax`)
     * will be set on cookies. If set to `None`, `$secure` must also be set.
     *
     * @var ''|'Lax'|'None'|'Strict'
     */
    public string $samesite = 'Lax';

    /**
     * --------------------------------------------------------------------------
     * Cookie Raw
     * --------------------------------------------------------------------------
     *
     * This flag allows setting a "raw" cookie, i.e., its name and value are
     * not URL encoded using `rawurlencode()`.
     *
     * If this is set to `true`, cookie names should be compliant of RFC 2616's
     * list of allowed characters.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#attributes
     * @see https://tools.ietf.org/html/rfc2616#section-2.2
     */
    public bool $raw = false;
}
