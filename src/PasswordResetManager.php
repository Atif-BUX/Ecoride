<?php
// Fichier: src/PasswordResetManager.php

require_once __DIR__ . '/Database.php';

class PasswordResetManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function ensureTable(): void
    {
        // Table minimaliste; en démo on évite les erreurs de FK/Engine potentiels
        $sql = "CREATE TABLE IF NOT EXISTS password_resets (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    expires_at DATETIME NOT NULL,
                    used_at DATETIME NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        try {
            $this->pdo->exec($sql);
        } catch (\Throwable $e) {
            // Fallback sans options d'engine/collation si l'hôte est restrictif
            $this->pdo->exec(str_replace(' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci', '', $sql));
        }
    }

    public function createReset(int $userId, int $ttlMinutes = 60): array
    {
        $this->ensureTable();
        // Invalider les anciens tokens pour cet utilisateur + tokens expirés
        $st = $this->pdo->prepare('DELETE FROM password_resets WHERE user_id = :uid OR expires_at < NOW()');
        $st->execute([':uid' => $userId]);

        $token = bin2hex(random_bytes(32));
        // Calcul d'expiration côté SGBD pour éviter tout décalage de fuseau horaire PHP/MySQL
        $ttl = max(1, (int)$ttlMinutes);
        $stmt = $this->pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $token, PDO::PARAM_STR);
        $stmt->bindValue(3, $ttl, PDO::PARAM_INT);
        $stmt->execute();
        // Récupérer la valeur réelle calculée par le SGBD
        $expAt = $this->pdo->query('SELECT DATE_ADD(NOW(), INTERVAL ' . (int)$ttl . ' MINUTE) AS exp')->fetch(PDO::FETCH_ASSOC)['exp'] ?? null;
        return ['token' => $token, 'expires_at' => $expAt];
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->pdo->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE :c');
            $stmt->execute([':c' => $column]);
            return (bool)$stmt->fetch();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getValidByToken(string $token): ?array
    {
        $this->ensureTable();
        // Déterminer le mode de stockage (tolérant): raw token, hash hex (CHAR(64)) ou binaire (BINARY(32))
        $hasRaw = $this->columnExists('password_resets', 'token');
        $hasHash = $this->columnExists('password_resets', 'token_hash');

        // Essai 1: colonne raw `token`
        if ($hasRaw) {
            $sql = 'SELECT pr.*, u.email, u.id AS user_id
                    FROM password_resets pr
                    JOIN users u ON u.id = pr.user_id
                    WHERE pr.token = :t AND pr.used_at IS NULL AND pr.expires_at >= NOW()
                    LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':t' => $token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) { return $row; }
        }

        if ($hasHash) {
            $hashHex = hash('sha256', $token);
            // Essai 2: stockage hex (CHAR(64))
            try {
                $sql = 'SELECT pr.*, u.email, u.id AS user_id
                        FROM password_resets pr
                        JOIN users u ON u.id = pr.user_id
                        WHERE pr.token_hash = :h AND pr.used_at IS NULL AND pr.expires_at >= NOW()
                        LIMIT 1';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':h' => $hashHex]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) { return $row; }
            } catch (\Throwable $e) { /* ignore */ }

            // Essai 3: stockage binaire (BINARY(32))
            try {
                $sql = 'SELECT pr.*, u.email, u.id AS user_id
                        FROM password_resets pr
                        JOIN users u ON u.id = pr.user_id
                        WHERE pr.token_hash = UNHEX(:h) AND pr.used_at IS NULL AND pr.expires_at >= NOW()
                        LIMIT 1';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':h' => $hashHex]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) { return $row; }
            } catch (\Throwable $e) { /* ignore */ }
        }

        return null;
    }

    public function useTokenAndUpdatePassword(string $token, string $newPasswordHash): bool
    {
        $this->pdo->beginTransaction();
        try {
            $row = $this->getValidByToken($token);
            if (!$row) { $this->pdo->rollBack(); return false; }
            $uid = (int)$row['user_id'];
            $u = $this->pdo->prepare('UPDATE users SET password = :p WHERE id = :id');
            $u->execute([':p' => $newPasswordHash, ':id' => $uid]);
            $m = $this->pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
            $m->execute([':id' => (int)$row['id']]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            try { $this->pdo->rollBack(); } catch (Throwable $ignored) {}
            return false;
        }
    }

    public static function logMailStub(string $to, string $subject, string $link): void
    {
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $line = '[' . date('c') . "] password_reset to={$to} subject=" . $subject . " link=" . $link . "\n";
        @file_put_contents($dir . '/mail.log', $line, FILE_APPEND);
        // Double trace côté serveur pour faciliter la mise au point en démo
        @error_log($line);
    }
}
