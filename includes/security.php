<?php
// Thin wrappers around CSRF helper for convenience in views/controllers

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return class_exists('Csrf') ? Csrf::input() : '';
    }
}

if (!function_exists('check_csrf_or_die')) {
    function check_csrf_or_die(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (!class_exists('Csrf') || !Csrf::validateRequest($token)) {
            http_response_code(403);
            exit('Requête refusée (CSRF). Veuillez réessayer.');
        }
    }
}

