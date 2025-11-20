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

if (!function_exists('currentUserRoles')) {
    function currentUserRoles(): array {
        return $_SESSION['user_roles'] ?? [];
    }
}

if (!function_exists('setCurrentUserRoles')) {
    function setCurrentUserRoles(array $roles): void {
        $_SESSION['user_roles'] = array_values(array_unique($roles));
    }
}

if (!function_exists('userHasRole')) {
    function userHasRole(string $role): bool {
        $role = strtoupper($role);
        foreach (currentUserRoles() as $assigned) {
            if (strtoupper($assigned) === $role) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('requireRole')) {
    function requireRole(string $role): void {
        if (!userHasRole($role)) {
            http_response_code(403);
            echo 'Accès refusé';
            exit();
        }
    }
}
