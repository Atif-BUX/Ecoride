<?php
// Fichier: src/UserProfileManager.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/UserManager.php';

class UserProfileManager
{
    private PDO $pdo;
    private UserManager $userManager;
    /** @var array<string,string[]> */
    private array $fieldAliases = [
        'phone'   => ['phone_number', 'telephone'],
        'address' => ['address', 'adresse', 'city', 'ville'],
        'birth'   => ['birth_date', 'date_naissance'],
        'pseudo'  => ['pseudo', 'username', 'display_name'],
        'photo'   => ['photo', 'profile_photo', 'avatar', 'photo_blob'],
        'photo_path' => ['photo_path', 'photo_url', 'avatar_url', 'profile_image', 'image_path'],
        'photo_mime' => ['photo_mime_type', 'avatar_mime_type', 'mime_type', 'photo_type'],
        'created_at' => ['created_at', 'date_inscription', 'joined_at', 'created_on'],
        'updated_at' => ['updated_at', 'modified_at'],
        'bio'        => ['bio', 'about']
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->userManager = new UserManager($pdo);
    }

    public function getProfile(int $userId): ?array
    {
        $raw = $this->userManager->getUserProfile($userId);
        if (!$raw) {
            return null;
        }

        $profile = [
            'id'         => $raw['id'] ?? null,
            'first_name' => $raw['first_name'] ?? null,
            'last_name'  => $raw['last_name'] ?? null,
            'email'      => $raw['email'] ?? null,
            'credit_balance' => (int)($raw['credit_balance'] ?? 0),
            'pseudo'     => $this->firstAvailable($raw, $this->fieldAliases['pseudo']),
            'phone'      => $this->firstAvailable($raw, $this->fieldAliases['phone']),
            'address'    => $this->firstAvailable($raw, $this->fieldAliases['address']),
            'birth_date' => $this->firstAvailable($raw, $this->fieldAliases['birth']),
            'created_at' => $this->firstAvailable($raw, $this->fieldAliases['created_at']),
            'updated_at' => $this->firstAvailable($raw, $this->fieldAliases['updated_at']),
            'bio'        => $this->firstAvailable($raw, $this->fieldAliases['bio']),
        ];

        $profile['photo_src'] = $this->buildPhotoSource($raw);

        return $profile;
    }

    public function updateProfile(int $userId, array $data): bool
    {
        $columns = $this->collectAvailableColumns();
        $updateMap = [
            'first_name' => $data['first_name'] ?? null,
            'last_name'  => $data['last_name'] ?? null,
            'email'      => $data['email'] ?? null,
        ];

        $this->assignIfPresent($updateMap, $columns, $this->fieldAliases['pseudo'], $data['pseudo'] ?? null);
        $this->assignIfPresent($updateMap, $columns, $this->fieldAliases['phone'], $data['phone'] ?? null);
        $this->assignIfPresent($updateMap, $columns, $this->fieldAliases['address'], $data['address'] ?? null);
        $this->assignIfPresent($updateMap, $columns, $this->fieldAliases['birth'], $data['birth_date'] ?? null);
        $this->assignIfPresent($updateMap, $columns, $this->fieldAliases['bio'], $data['bio'] ?? null);

        $updateMap = array_filter(
            $updateMap,
            static fn($value) => $value !== null
        );

        if (empty($updateMap)) {
            return true;
        }

        $set = [];
        $params = [':id' => $userId];

        foreach ($updateMap as $column => $value) {
            $param = ':' . $column;
            $set[] = "{$column} = {$param}";
            $params[$param] = $value;
        }

        if (isset($columns['updated_at'])) {
            $set[] = "updated_at = CURRENT_TIMESTAMP";
        }

        $sql = 'UPDATE users SET ' . implode(', ', $set) . ' WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateAvatar(int $userId, ?string $binaryContent, ?string $mimeType): bool
    {
        $columns = $this->collectAvailableColumns();
        $photoColumn = $this->resolveColumn($columns, $this->fieldAliases['photo']);
        $pathColumn  = $this->resolveColumn($columns, $this->fieldAliases['photo_path']);
        $mimeColumn  = $this->resolveColumn($columns, $this->fieldAliases['photo_mime']);

        if ($photoColumn === null && $pathColumn === null) {
            return false;
        }

        $setParts = [];
        $params = [':id' => $userId];

        if ($photoColumn !== null) {
            $setParts[] = "{$photoColumn} = :photo_bin";
            $params[':photo_bin'] = $binaryContent;
        }

        if ($mimeColumn !== null) {
            $setParts[] = "{$mimeColumn} = :mime";
            $params[':mime'] = $mimeType;
        }

        if ($pathColumn !== null && $photoColumn === null) {
            $setParts[] = "{$pathColumn} = :photo_path";
            $params[':photo_path'] = $binaryContent;
        } elseif ($pathColumn !== null) {
            $setParts[] = "{$pathColumn} = NULL";
        }

        if (isset($columns['updated_at'])) {
            $setParts[] = "updated_at = CURRENT_TIMESTAMP";
        }

        $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function removeAvatar(int $userId): bool
    {
        return $this->updateAvatar($userId, null, null);
    }

    private function collectAvailableColumns(): array
    {
        $columns = $this->getUserColumns();
        $map = [];
        foreach ($columns as $column) {
            $map[$column] = true;
        }
        return $map;
    }

    private function getUserColumns(): array
    {
        $ref = new ReflectionClass(UserManager::class);
        if ($ref->hasMethod('getTableColumns')) {
            $method = $ref->getMethod('getTableColumns');
            $method->setAccessible(true);
            /** @var array $columns */
            $columns = $method->invoke($this->userManager, 'users');
            return $columns;
        }
        return [];
    }

    private function assignIfPresent(array &$updateMap, array $columns, array $candidates, $value): void
    {
        if ($value === null) {
            return;
        }
        $column = $this->resolveColumn($columns, $candidates);
        if ($column !== null) {
            $updateMap[$column] = $value;
        }
    }

    private function resolveColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (isset($columns[$candidate])) {
                return $candidate;
            }
        }
        return null;
    }

    private function firstAvailable(array $source, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (!array_key_exists($candidate, $source)) {
                continue;
            }
            $value = $source[$candidate];
            if ($value === null) {
                continue;
            }
            if (is_resource($value)) {
                $value = stream_get_contents($value);
            }
            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed !== '') {
                    return $trimmed;
                }
            } elseif (is_numeric($value)) {
                return (string)$value;
            }
        }
        return null;
    }

    private function buildPhotoSource(array $raw): ?string
    {
        $binary = $this->firstAvailable($raw, $this->fieldAliases['photo']);
        if ($binary !== null) {
            $mime = $this->firstAvailable($raw, $this->fieldAliases['photo_mime']) ?? 'image/jpeg';
            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        $path = $this->firstAvailable($raw, $this->fieldAliases['photo_path']);
        return $path ?: null;
    }
}
