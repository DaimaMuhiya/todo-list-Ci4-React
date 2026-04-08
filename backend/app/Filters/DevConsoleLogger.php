<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * En development only : journalise chaque requête HTTP sur la sortie d’erreur PHP
 * (terminal où tourne `php spark serve`). Évite la constante STDERR (absente en built-in server).
 */
class DevConsoleLogger implements FilterInterface
{
    private const STDERR_URI = 'php://stderr';

    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (ENVIRONMENT !== 'development') {
            return null;
        }

        if (! $request instanceof IncomingRequest) {
            return null;
        }

        $start = isset($_SERVER['REQUEST_TIME_FLOAT']) && is_float($_SERVER['REQUEST_TIME_FLOAT'])
            ? $_SERVER['REQUEST_TIME_FLOAT']
            : microtime(true);
        $durationMs = round((microtime(true) - $start) * 1000, 2);

        $method = $request->getMethod();
        $uri = $request->getUri();
        $path = $uri->getPath() ?: '/';
        $query = $uri->getQuery();
        if ($query !== '') {
            $path .= '?' . $query;
        }

        $status = $response->getStatusCode();
        $time = date('Y-m-d H:i:s');
        $statusColored = $this->wrapStatus((int) $status);
        $line = sprintf(
            "[%s] %s %-6s %s → %s %s ms\n",
            $time,
            $this->dim('HTTP'),
            $method,
            $path,
            $statusColored,
            $durationMs,
        );

        $this->writeStderr($line);

        return null;
    }

    private function writeStderr(string $line): void
    {
        $fh = @fopen(self::STDERR_URI, 'wb');
        if ($fh !== false) {
            fwrite($fh, $line);
            fclose($fh);
        }
    }

    private function dim(string $label): string
    {
        if ($this->stderrColor()) {
            return "\033[2m" . $label . "\033[0m";
        }

        return $label;
    }

    private function wrapStatus(int $status): string
    {
        $text = (string) $status;

        if (! $this->stderrColor()) {
            return $text;
        }

        if ($status >= 500) {
            return "\033[31m" . $text . "\033[0m";
        }

        if ($status >= 400) {
            return "\033[33m" . $text . "\033[0m";
        }

        return "\033[32m" . $text . "\033[0m";
    }

    private function stderrColor(): bool
    {
        if (! function_exists('stream_isatty')) {
            return false;
        }

        $fh = @fopen(self::STDERR_URI, 'rb');
        if ($fh === false) {
            return false;
        }

        $tty = @stream_isatty($fh);
        fclose($fh);

        return $tty === true;
    }
}
