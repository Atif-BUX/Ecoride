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
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] !== '') {
            $o = parse_url($_SERVER['HTTP_ORIGIN']);
            return isset($o['host']) && strcasecmp($o['host'], $host) === 0;
        }
        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== '') {
            $r = parse_url($_SERVER['HTTP_REFERER']);
            return isset($r['host']) && strcasecmp($r['host'], $host) === 0;
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
