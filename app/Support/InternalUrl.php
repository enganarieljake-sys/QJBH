<?php

namespace App\Support;

class InternalUrl
{
    /** Build a canonical absolute URL for email links. */
    public static function absolute(string $path): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $host = parse_url($base, PHP_URL_HOST);

        // Vercel provides its production hostname automatically. Use it as a
        // safety net if a stale localhost APP_URL ever reaches production.
        if (app()->environment('production') && in_array($host, ['localhost', '127.0.0.1'], true)) {
            $vercelHost = env('VERCEL_PROJECT_PRODUCTION_URL') ?: env('VERCEL_URL');

            if (is_string($vercelHost) && $vercelHost !== '') {
                $base = 'https://'.ltrim($vercelHost, '/');
            }
        }

        return $base.self::path($path, '/');
    }

    /**
     * Convert an application URL to a host-independent path.
     *
     * Notifications can outlive an APP_URL change (for example, localhost to
     * Vercel), so persisted links must not retain the host that created them.
     */
    public static function path(?string $url, string $fallback = '/dashboard'): string
    {
        if (! is_string($url) || trim($url) === '') {
            return $fallback;
        }

        $url = trim($url);

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['path'])) {
            return $fallback;
        }

        $path = '/'.ltrim($parts['path'], '/');

        if (isset($parts['query']) && $parts['query'] !== '') {
            $path .= '?'.$parts['query'];
        }

        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $path .= '#'.$parts['fragment'];
        }

        return $path;
    }
}
