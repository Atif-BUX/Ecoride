<?php
// Fichier: src/VehicleManager.php

require_once __DIR__ . '/Database.php';

class VehicleManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* ----------------------------------------------------------------------
     * Brands
     * -------------------------------------------------------------------- */

    public function createBrand(string $label): ?int
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }

        // Vérifier si la marque existe déjà
        $sql = 'SELECT id FROM brands WHERE label = :label LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([':label' => $label]);
            $existing = $stmt->fetchColumn();
            if ($existing !== false) {
                return (int)$existing;
            }

            $insert = $this->pdo->prepare('INSERT INTO brands (label) VALUES (:label)');
            $insert->execute([':label' => $label]);
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('VehicleManager::createBrand ' . $e->getMessage());
            return null;
        }
    }

    public function listBrands(): array
    {
        $sql = 'SELECT id, label FROM brands ORDER BY label';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ensureBrand(?string $label): ?int
    {
        if ($label === null) {
            return null;
        }
        return $this->createBrand($label);
    }

    /* ----------------------------------------------------------------------
     * Vehicles
     * -------------------------------------------------------------------- */

    public function registerVehicle(int $userId, array $data): ?int
    {
        $sql = 'INSERT INTO vehicles (
                    user_id, brand_id, model, license_plate, energy, color,
                    first_registration_date, description
                ) VALUES (
                    :user_id, :brand_id, :model, :license_plate, :energy, :color,
                    :first_registration_date, :description
                )';

        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                ':user_id' => $userId,
                ':brand_id' => $data['brand_id'] ?? null,
                ':model' => $data['model'],
                ':license_plate' => $data['license_plate'],
                ':energy' => $data['energy'] ?? null,
                ':color' => $data['color'] ?? null,
                ':first_registration_date' => $data['first_registration_date'] ?? null,
                ':description' => $data['description'] ?? null,
            ]);
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('VehicleManager::registerVehicle ' . $e->getMessage());
            return null;
        }
    }

    public function updateVehicle(int $vehicleId, int $userId, array $data): bool
    {
        $sql = 'UPDATE vehicles SET
                    brand_id = :brand_id,
                    model = :model,
                    license_plate = :license_plate,
                    energy = :energy,
                    color = :color,
                    first_registration_date = :first_registration_date,
                    description = :description
                WHERE id = :id AND user_id = :user_id';

        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                ':brand_id' => $data['brand_id'] ?? null,
                ':model' => $data['model'],
                ':license_plate' => $data['license_plate'],
                ':energy' => $data['energy'] ?? null,
                ':color' => $data['color'] ?? null,
                ':first_registration_date' => $data['first_registration_date'] ?? null,
                ':description' => $data['description'] ?? null,
                ':id' => $vehicleId,
                ':user_id' => $userId,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('VehicleManager::updateVehicle ' . $e->getMessage());
            return false;
        }
    }

    public function deleteVehicle(int $vehicleId, int $userId): bool
    {
        $sql = 'DELETE FROM vehicles WHERE id = :id AND user_id = :user_id';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([':id' => $vehicleId, ':user_id' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('VehicleManager::deleteVehicle ' . $e->getMessage());
            return false;
        }
    }

    public function getVehiclesByUser(int $userId): array
    {
        $sql = 'SELECT v.*, b.label AS brand_label
                FROM vehicles v
                LEFT JOIN brands b ON b.id = v.brand_id
                WHERE v.user_id = :user_id
                ORDER BY v.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVehicle(int $vehicleId, int $userId): ?array
    {
        $sql = 'SELECT v.*, b.label AS brand_label
                FROM vehicles v
                LEFT JOIN brands b ON b.id = v.brand_id
                WHERE v.id = :id AND v.user_id = :user_id
                LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $vehicleId, ':user_id' => $userId]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        return $vehicle ?: null;
    }

    public function attachVehicleToTravel(int $travelId, int $vehicleId, int $userId): bool
    {
        $sql = 'UPDATE travels SET vehicle_id = :vehicle_id WHERE id = :travel_id AND user_id = :user_id';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                ':vehicle_id' => $vehicleId,
                ':travel_id' => $travelId,
                ':user_id' => $userId,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('VehicleManager::attachVehicleToTravel ' . $e->getMessage());
            return false;
        }
    }

    public function detachVehicleFromTravel(int $travelId, int $userId): bool
    {
        $sql = 'UPDATE travels SET vehicle_id = NULL WHERE id = :travel_id AND user_id = :user_id';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([':travel_id' => $travelId, ':user_id' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('VehicleManager::detachVehicleFromTravel ' . $e->getMessage());
            return false;
        }
    }
}
