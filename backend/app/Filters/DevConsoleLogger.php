<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * En development only : journalise chaque requête HTTP sur STDERR
 * (terminal où tourne `php spark serve`), sur le modèle d’un accès log / Winston.
 */
class DevConsoleLogger implements FilterInterface
{
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

        fwrite(STDERR, $line);

        return null;
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
        return function_exists('stream_isatty') && @stream_isatty(STDERR);
    }
}
