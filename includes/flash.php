<?php
// Simple flash message helper stored in session

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null): ?string {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }
}

if (!function_exists('has_flash')) {
    function has_flash(string $key): bool {
        return isset($_SESSION['_flash'][$key]);
    }
}

