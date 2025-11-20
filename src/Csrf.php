<?php
// Simple CSRF helper for forms

class Csrf
{
    public static function ensureToken(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function token(): string
    {
        self::ensureToken();
        return (string)($_SESSION['csrf_token'] ?? '');
    }

    public static function input(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate(string $token): bool
    {
        $expected = $_SESSION['csrf_token'] ?? '';
        if (!is_string($expected) || $expected === '' || !is_string($token)) {
            return false;
        }
        return hash_equals($expected, $token);
    }

    private static function sameOrigin(): bool
    {
        [$host, $port] = self::requestOriginParts();

        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] !== '') {
            return self::matchesOrigin(parse_url($_SERVER['HTTP_ORIGIN']), $host, $port);
        }
        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== '') {
            return self::matchesOrigin(parse_url($_SERVER['HTTP_REFERER']), $host, $port);
        }
        return true;
    }

    private static function requestOriginParts(): array
    {
        $hostHeader = $_SERVER['HTTP_HOST'] ?? '';
        $host = $hostHeader;
        $port = null;

        if (strpos($hostHeader, ':') !== false) {
            [$host, $port] = explode(':', $hostHeader, 2);
        } elseif (isset($_SERVER['SERVER_PORT'])) {
            $port = (int) $_SERVER['SERVER_PORT'];
        }

        $host = strtolower($host);
        if ($port !== null) {
            $port = (int) $port;
        }

        return [$host, $port];
    }

    private static function matchesOrigin(array|false $parts, string $host, ?int $port): bool
    {
        if ($parts === false || empty($parts['host'])) {
            return false;
        }

        $candidateHost = strtolower($parts['host']);
        $candidatePort = isset($parts['port']) ? (int) $parts['port'] : null;

        if ($candidateHost !== $host) {
            return false;
        }

        if ($port !== null && $candidatePort !== null && $port !== $candidatePort) {
            return false;
        }

        return true;
    }

    public static function validateRequest(string $token): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!self::sameOrigin()) { return false; }
        }
        return self::validate($token);
    }
}
