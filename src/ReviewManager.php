<?php
// Fichier: src/ReviewManager.php

require_once __DIR__ . '/Database.php';

class ReviewManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function leaveReview(int $reviewerId, int $reviewedUserId, int $travelId, int $rating, ?string $comment = null): bool
    {
        $rating = max(1, min(5, $rating));
        $sql = 'INSERT INTO reviews (travel_id, reviewer_id, reviewed_user_id, rating, comment, status)
                VALUES (:travel_id, :reviewer_id, :reviewed_user_id, :rating, :comment, :status)
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), status = VALUES(status), updated_at = CURRENT_TIMESTAMP';

        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                ':travel_id' => $travelId,
                ':reviewer_id' => $reviewerId,
                ':reviewed_user_id' => $reviewedUserId,
                ':rating' => $rating,
                ':comment' => $comment,
                // Default to pending so employees can moderate before publish
                ':status' => 'pending'
            ]);
            return true;
        } catch (PDOException $e) {
            error_log('ReviewManager::leaveReview ' . $e->getMessage());
            return false;
        }
    }

    public function publishReview(int $reviewId): bool
    {
        return $this->setStatus($reviewId, 'published');
    }

    public function rejectReview(int $reviewId): bool
    {
        return $this->setStatus($reviewId, 'rejected');
    }

    private function setStatus(int $reviewId, string $status): bool
    {
        $status = in_array($status, ['pending', 'published', 'rejected'], true) ? $status : 'pending';
        $sql = 'UPDATE reviews SET status = :status WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([':status' => $status, ':id' => $reviewId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('ReviewManager::setStatus ' . $e->getMessage());
            return false;
        }
    }

    public function listReviewsForUser(int $userId, bool $onlyPublished = true): array
    {
        $sql = 'SELECT r.*, u.first_name AS reviewer_first_name, u.last_name AS reviewer_last_name,
                       t.departure_city, t.arrival_city, t.departure_date
                FROM reviews r
                JOIN users u ON u.id = r.reviewer_id
                JOIN travels t ON t.id = r.travel_id
                WHERE r.reviewed_user_id = :user_id';
        if ($onlyPublished) {
            $sql .= " AND r.status = 'published'";
        }
        $sql .= ' ORDER BY r.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function averageRatingForUser(int $userId): ?float
    {
        $sql = "SELECT AVG(rating) AS avg_rating
                FROM reviews
                WHERE reviewed_user_id = :user_id AND status = 'published'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['avg_rating'] === null) {
            return null;
        }
        return (float)$row['avg_rating'];
    }

    public function getReviewForTravel(int $travelId, int $reviewerId): ?array
    {
        $sql = 'SELECT * FROM reviews WHERE travel_id = :travel_id AND reviewer_id = :reviewer_id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':travel_id' => $travelId,
            ':reviewer_id' => $reviewerId,
        ]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        return $review ?: null;
    }

    public function publishPendingForUser(int $reviewedUserId): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE reviews
                 SET status = 'published', updated_at = CURRENT_TIMESTAMP
                 WHERE reviewed_user_id = :user_id AND status <> 'published'"
            );
            $stmt->execute([':user_id' => $reviewedUserId]);
        } catch (PDOException $e) {
            error_log('ReviewManager::publishPendingForUser ' . $e->getMessage());
        }
    }
}
