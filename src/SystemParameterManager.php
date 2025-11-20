<?php
// Fichier: src/SystemParameterManager.php

require_once __DIR__ . '/Database.php';

class SystemParameterManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get(string $property, $default = null): mixed
    {
        $sql = 'SELECT cp.value
                FROM configuration_parameters cp
                JOIN configurations c ON c.id = cp.configuration_id
                JOIN parameters p ON p.id = cp.parameter_id
                WHERE c.label = :cfg AND p.property = :property
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cfg' => 'default',
            ':property' => $property
        ]);

        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    }

    public function set(string $property, string $value, string $configuration = 'default'): bool
    {
        $cfgId = $this->ensureConfiguration($configuration);
        $paramId = $this->ensureParameter($property);

        $sql = 'INSERT INTO configuration_parameters (configuration_id, parameter_id, value)
                VALUES (:cfg, :param, :value)
                ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':cfg' => $cfgId,
            ':param' => $paramId,
            ':value' => $value
        ]);
    }

    public function listAll(string $configuration = 'default'): array
    {
        $sql = 'SELECT p.property, COALESCE(cp.value, p.default_value) AS value
                FROM parameters p
                LEFT JOIN configuration_parameters cp ON cp.parameter_id = p.id
                LEFT JOIN configurations c ON c.id = cp.configuration_id
                WHERE c.label = :cfg OR c.id IS NULL
                ORDER BY p.property';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cfg' => $configuration]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    private function ensureConfiguration(string $configuration): int
    {
        $sql = 'SELECT id FROM configurations WHERE label = :label LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':label' => $configuration]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int)$id;
        }

        $stmt = $this->pdo->prepare('INSERT INTO configurations (label) VALUES (:label)');
        $stmt->execute([':label' => $configuration]);
        return (int)$this->pdo->lastInsertId();
    }

    private function ensureParameter(string $property): int
    {
        $sql = 'SELECT id FROM parameters WHERE property = :property LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':property' => $property]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int)$id;
        }

        $stmt = $this->pdo->prepare('INSERT INTO parameters (property) VALUES (:property)');
        $stmt->execute([':property' => $property]);
        return (int)$this->pdo->lastInsertId();
    }
}
