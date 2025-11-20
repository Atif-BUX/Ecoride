<?php
// Fichier: src/RoleManager.php

require_once __DIR__ . '/Database.php';

class RoleManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function ensureRole(string $label): int
    {
        $sql = 'SELECT id FROM roles WHERE label = :label LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':label' => $label]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int)$id;
        }

        $stmt = $this->pdo->prepare('INSERT INTO roles (label) VALUES (:label)');
        $stmt->execute([':label' => $label]);
        return (int)$this->pdo->lastInsertId();
    }

    public function assignRole(int $userId, string $label): bool
    {
        $roleId = $this->ensureRole($label);
        $sql = 'INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)';
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([':user_id' => $userId, ':role_id' => $roleId]);

        $this->setPrimaryRoleIfMissing($userId, $roleId);
        return $ok;
    }

    public function removeRole(int $userId, string $label): bool
    {
        $sql = 'DELETE ur FROM user_roles ur
                JOIN roles r ON r.id = ur.role_id
                WHERE ur.user_id = :user_id AND r.label = :label';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':label' => $label]);
        return $stmt->rowCount() > 0;
    }

    public function userHasRole(int $userId, string $label): bool
    {
        $sql = 'SELECT 1
                FROM user_roles ur
                JOIN roles r ON r.id = ur.role_id
                WHERE ur.user_id = :user_id AND r.label = :label
                LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':label' => $label]);
        return (bool)$stmt->fetchColumn();
    }

    public function listRolesForUser(int $userId): array
    {
        $sql = 'SELECT r.label
                FROM user_roles ur
                JOIN roles r ON r.id = ur.role_id
                WHERE ur.user_id = :user_id
                ORDER BY r.label';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function setPrimaryRoleIfMissing(int $userId, int $roleId): void
    {
        $sql = 'UPDATE users SET role_primary_id = :role_id
                WHERE id = :id AND (role_primary_id IS NULL OR role_primary_id = 0)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':role_id' => $roleId, ':id' => $userId]);
    }
}
