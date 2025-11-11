<?php
// Fichier: src/CreditManager.php

require_once __DIR__ . '/Database.php';

class CreditManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getBalance(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT credit_balance FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        return (int)($stmt->fetchColumn() ?? 0);
    }

    public function hasSufficientBalance(int $userId, int $requiredAmount): bool
    {
        return $this->getBalance($userId) >= $requiredAmount;
    }

    public function adjustBalance(int $userId, int $amount, string $type, ?int $reservationId = null, string $note = ''): bool
    {
        $update = $this->pdo->prepare('UPDATE users SET credit_balance = credit_balance + :amount WHERE id = :id');
        if (!$update->execute([':amount' => $amount, ':id' => $userId])) {
            return false;
        }

        $log = $this->pdo->prepare(
            'INSERT INTO credit_transactions (user_id, reservation_id, amount, type, note)
             VALUES (:user_id, :reservation_id, :amount, :type, :note)'
        );
        return $log->execute([
            ':user_id' => $userId,
            ':reservation_id' => $reservationId,
            ':amount' => $amount,
            ':type' => $type,
            ':note' => $note
        ]);
    }

    public function listTransactions(int $userId, int $limit = 10): array
    {
        $sql = 'SELECT amount, type, note, created_at
                FROM credit_transactions
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT :lim';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
