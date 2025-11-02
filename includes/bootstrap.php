<?php
// Common bootstrap: session, timezone, security defaults, CSRF, helpers

// Secure cookie params (best effort; Secure only effective over HTTPS)
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Europe/Paris');

// Load CSRF helper and ensure a token is present
$__csrf = __DIR__ . '/../src/Csrf.php';
if (file_exists($__csrf)) {
    require_once $__csrf;
    if (class_exists('Csrf')) { Csrf::ensureToken(); }
}

// Load small helper sets
$__sec = __DIR__ . '/security.php';
if (file_exists($__sec)) { require_once $__sec; }
$__flash = __DIR__ . '/flash.php';
if (file_exists($__flash)) { require_once $__flash; }
$__auth = __DIR__ . '/auth.php';
if (file_exists($__auth)) { require_once $__auth; }

// Basic error visibility toggle via env-like flag
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}
ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(APP_DEBUG ? E_ALL : E_ALL & ~E_NOTICE & ~E_DEPRECATED);

