<?php

declare(strict_types=1);

/**
 * Render / Docker : le depot exclut .env du build (.dockerignore).
 * Recree un .env au demarrage si le fichier est absent ou vide, a partir de :
 * - variables Laravel MAIL_* (recommande sur Render), ou
 * - variables email.* / email_* si le PaaS les injecte (souvent visibles en CLI).
 *
 * Apache + mod_php ne voit parfois pas les cles avec des points ; le fichier .env
 * permet a CodeIgniter de les charger comme en local.
 */

$root    = dirname(__DIR__);
$envPath = $root . DIRECTORY_SEPARATOR . '.env';

if (is_file($envPath) && filesize($envPath) > 0) {
    exit(0);
}

/**
 * @return array<string, string>|null
 */
function mapFromMailStar(): ?array
{
    $host = getenv('MAIL_HOST') ?: getenv('EMAIL_SMTP_HOST') ?: '';
    if ($host === '') {
        return null;
    }

    $fromEmail = getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_FROM') ?: getenv('EMAIL_FROM_EMAIL') ?: '';
    $user      = getenv('MAIL_USERNAME') ?: getenv('EMAIL_SMTP_USER') ?: '';
    $pass      = getenv('MAIL_PASSWORD') ?: getenv('EMAIL_SMTP_PASS') ?: '';

    if ($fromEmail === '' || $user === '' || $pass === '') {
        return null;
    }

    $pass = preg_replace('/\s+/u', '', $pass) ?? $pass;

    return [
        'email.protocol'   => 'smtp',
        'email.fromEmail'  => $fromEmail,
        'email.fromName'   => getenv('MAIL_FROM_NAME') ?: getenv('EMAIL_FROM_NAME') ?: 'TaskFlow',
        'email.smtpHost'   => $host,
        'email.smtpPort'   => getenv('MAIL_PORT') ?: getenv('EMAIL_SMTP_PORT') ?: '587',
        'email.smtpUser'   => $user,
        'email.smtpPass'   => $pass,
        'email.SMTPCrypto' => getenv('MAIL_ENCRYPTION') ?: getenv('EMAIL_SMTP_CRYPTO') ?: 'tls',
    ];
}

/**
 * @param list<string> $keys
 */
function getenvFirst(string ...$keys): string
{
    foreach ($keys as $key) {
        foreach ([$key, str_replace('.', '_', $key)] as $k) {
            $v = getenv($k);
            if ($v !== false && trim((string) $v) !== '') {
                return trim((string) $v);
            }
        }
    }

    return '';
}

/**
 * @return array<string, string>|null
 */
function mapFromEmailDotKeys(): ?array
{
    $host = getenvFirst('email.smtpHost');
    if ($host === '') {
        return null;
    }

    $fromEmail = getenvFirst('email.fromEmail');
    $user      = getenvFirst('email.smtpUser');
    $pass      = getenvFirst('email.smtpPass');

    if ($fromEmail === '' || $user === '' || $pass === '') {
        return null;
    }

    $pass = preg_replace('/\s+/u', '', $pass) ?? $pass;

    $proto = getenvFirst('email.protocol');
    if ($proto === '') {
        $proto = 'smtp';
    }

    $map = [
        'email.protocol'   => strtolower($proto) === 'smtp' ? 'smtp' : $proto,
        'email.fromEmail'  => $fromEmail,
        'email.fromName'   => getenvFirst('email.fromName') ?: 'TaskFlow',
        'email.smtpHost'   => $host,
        'email.smtpPort'   => getenvFirst('email.smtpPort') ?: '587',
        'email.smtpUser'   => $user,
        'email.smtpPass'   => $pass,
        'email.SMTPCrypto' => getenvFirst('email.SMTPCrypto', 'email.smtpCrypto') ?: 'tls',
    ];

    return $map;
}

$map = mapFromMailStar() ?? mapFromEmailDotKeys();

if ($map === null) {
    fwrite(STDERR, "sync-env-from-mail: aucune config MAIL_* complete ni email.smtpHost + from + user + pass dans l environnement.\n");

    exit(0);
}

$lines = [
    '# Fichier genere au demarrage (Render / Docker). Ne pas commiter.',
];

foreach ($map as $key => $value) {
    $value = (string) $value;
    if ($value === '') {
        continue;
    }
    if (preg_match('/[#"\n\\\\]/u', $value) || str_contains($value, ' ')) {
        $value = '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }
    $lines[] = "{$key} = {$value}";
}

file_put_contents($envPath, implode("\n", $lines) . "\n");
chmod($envPath, 0600);

fwrite(STDERR, "sync-env-from-mail: .env ecrit (" . count($map) . " cles e-mail).\n");
