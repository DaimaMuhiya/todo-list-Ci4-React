<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = '';
    public string $fromName   = '';
    public string $recipients = '';

    /**
     * The "user agent"
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     */
    public string $protocol = 'mail';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname
     */
    public string $SMTPHost = '';

    /**
     * Which SMTP authentication method to use: login, plain
     */
    public string $SMTPAuthMethod = 'login';

    /**
     * SMTP Username
     */
    public string $SMTPUser = '';

    /**
     * SMTP Password
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port
     */
    public int $SMTPPort = 25;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 5;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption.
     *
     * @var string '', 'tls' or 'ssl'. 'tls' will issue a STARTTLS command
     *             to the server. 'ssl' means implicit SSL. Connection on port
     *             465 should set this to ''.
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     */
    public string $mailType = 'text';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;

    public function __construct()
    {
        parent::__construct();

        if ($this->fromEmail === '') {
            $this->fromEmail = self::envAny(
                'email.fromEmail',
                'MAIL_FROM_ADDRESS',
                'MAIL_FROM',
                'EMAIL_FROM_EMAIL',
                'EMAIL_FROM',
            );
        }

        if ($this->fromName === '') {
            $this->fromName = self::envAny(
                'email.fromName',
                'MAIL_FROM_NAME',
                'EMAIL_FROM_NAME',
            );
        }

        $proto = self::envAny('email.protocol', 'MAIL_MAILER', 'EMAIL_PROTOCOL');
        if ($proto !== '') {
            $p = strtolower($proto);
            if ($p === 'smtp' || $p === 'mail' || $p === 'sendmail') {
                $this->protocol = $p;
            }
        }

        if ($this->SMTPHost === '') {
            $this->SMTPHost = self::envAny(
                'email.smtpHost',
                'MAIL_HOST',
                'SMTP_HOST',
                'EMAIL_SMTP_HOST',
            );
        }

        if ($this->SMTPUser === '') {
            $this->SMTPUser = trim(self::envAny(
                'email.smtpUser',
                'MAIL_USERNAME',
                'SMTP_USER',
                'EMAIL_SMTP_USER',
            ));
        }

        if ($this->SMTPPass === '') {
            $raw = self::envAny(
                'email.smtpPass',
                'MAIL_PASSWORD',
                'SMTP_PASS',
                'EMAIL_SMTP_PASS',
            );
            if ($raw !== '') {
                $this->SMTPPass = self::normalizeSmtpPassword($raw);
            }
        }

        $portStr = self::envAny('email.smtpPort', 'MAIL_PORT', 'EMAIL_SMTP_PORT');
        if ($portStr !== '') {
            $this->SMTPPort = (int) $portStr;
        }

        $crypto = self::envAny(
            'email.smtpCrypto',
            'email.SMTPCrypto',
            'MAIL_ENCRYPTION',
            'EMAIL_SMTP_CRYPTO',
        );
        if ($crypto !== '') {
            $this->SMTPCrypto = strtolower($crypto) === 'ssl' ? 'ssl' : $crypto;
        }

        $timeoutStr = self::envAny('email.smtpTimeout', 'MAIL_TIMEOUT', 'EMAIL_SMTP_TIMEOUT');
        if ($timeoutStr !== '') {
            $this->SMTPTimeout = max(5, (int) $timeoutStr);
        }

        // Sur les PaaS, « mail » ne fonctionne pas : si un SMTP est configure, utiliser smtp.
        if ($this->SMTPHost !== '' && $this->protocol === 'mail') {
            $this->protocol = 'smtp';
        }

        // Connexion SMTP depuis un hebergeur (Render, etc.) : delai court = echecs frequents.
        if ($this->protocol === 'smtp' && $this->SMTPHost !== '' && $this->SMTPTimeout < 20) {
            $this->SMTPTimeout = 30;
        }
    }

    /**
     * @param list<string> $keys Cles .env (points), Laravel (MAIL_*), ou sans point (Render, etc.)
     */
    private static function envAny(string ...$keys): string
    {
        foreach ($keys as $key) {
            $variants = [$key];
            if (str_contains($key, '.')) {
                $variants[] = str_replace('.', '_', $key);
            }

            foreach ($variants as $k) {
                $t = self::envRaw($k);
                if ($t !== '') {
                    return $t;
                }
            }
        }

        return '';
    }

    /**
     * Lecture robuste : CodeIgniter env(), puis $_SERVER, puis getenv()
     * (certains hebergeurs ne remplissent pas ce que lit env() seul).
     */
    private static function envRaw(string $key): string
    {
        $v = env($key);
        if (\is_string($v)) {
            $t = trim($v);
            if ($t !== '') {
                return $t;
            }
        }

        if (isset($_SERVER[$key]) && \is_string($_SERVER[$key])) {
            $t = trim($_SERVER[$key]);
            if ($t !== '') {
                return $t;
            }
        }

        $g = getenv($key);
        if ($g !== false) {
            $t = trim((string) $g);
            if ($t !== '') {
                return $t;
            }
        }

        return '';
    }

    private static function normalizeSmtpPassword(string $pass): string
    {
        $s = trim($pass);
        if ($s !== '' && strlen($s) >= 2) {
            $first = $s[0];
            $last  = $s[strlen($s) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $s = substr($s, 1, -1);
            }
        }

        $collapsed = preg_replace('/\s+/u', '', $s);

        return $collapsed ?? $s;
    }
}
