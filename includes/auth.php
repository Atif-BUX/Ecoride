<?php
// Minimal auth helpers

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool { return !empty($_SESSION['is_logged_in']); }
}

if (!function_exists('currentUserId')) {
    function currentUserId(): int { return (int)($_SESSION['user_id'] ?? 0); }
}

if (!function_exists('requireLogin')) {
    function requireLogin(): void {
        if (!isLoggedIn()) { header('Location: connexion.php'); exit(); }
    }
}

