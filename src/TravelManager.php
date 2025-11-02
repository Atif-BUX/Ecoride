<?php
// Fichier: src/TravelManager.php - 02/11/2025 - 18:15

/**
 * Gère les opérations de base de données liées aux trajets (covoiturages).
 */
class TravelManager {
    // Utilisation de la classe complète \PDO
    private PDO $pdo;

    /**
     * Constructeur. Nécessite une instance PDO valide pour fonctionner.
     * @param PDO $pdo L'objet de connexion à la base de données.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Construit la clause SELECT de base avec les colonnes de T(travels) et les infos utilisateur U(users).
     * @param string $columnsColonnes spécifiques à sélectionner (par défaut T.*, U.first_name, U.last_name).
     * @return string La clause SQL SELECT...FROM...JOIN.
     */
    private function getBaseSelectQuery(string $columns = 'T.*, U.first_name, U.last_name'): string {
        return "SELECT
            {$columns}
        FROM
            travels T
        JOIN
            users U ON T.user_id = U.id";
    }

    /**
     * Insère un nouveau trajet dans la base de données.
     * Utilisation de marqueurs nommés pour plus de clarté.
     * @param int $userId L'ID de l'utilisateur qui propose le trajet.
     * @param array $data Les données nettoyées du formulaire.
     * @return bool Vrai en cas de succès, Faux sinon.
     */
    public function createTravel(int $userId, array $data): bool {
        // Ajout de total_seats (supposé être égal à available_seats à la création)
        $sql = "INSERT INTO travels (
                   user_id, departure_city, arrival_city, departure_date, 
                   departure_time, available_seats, price_per_seat, description, total_seats
               ) VALUES (
                   :user_id, :departure_city, :arrival_city, :departure_date, 
                   :departure_time, :available_seats, :price_per_seat, :description, :total_seats
               )";

        try {
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                ':user_id'          => $userId,
                ':departure_city'   => $data['depart'],
                ':arrival_city'     => $data['arrivee'],
                ':departure_date'   => $data['date_depart'],
                ':departure_time'   => $data['heure_depart'],
                ':available_seats'  => $data['seats'],
                ':price_per_seat'   => $data['price'],
                ':description'      => $data['description'],
                ':total_seats'      => $data['seats'] // Même valeur que 'seats' à la création
            ]);
        } catch (PDOException $e) { // Utilisation de la classe complète \PDOException
            error_log("DB Error in createTravel: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create travel while optionally handling car_details column if it exists.
     * Leaves the original API intact.
     */
    public function createTravelWithOptionalCarDetails(int $userId, array $data): bool {
        $hasCarDetails = $this->hasColumn('travels', 'car_details');

        if ($hasCarDetails) {
            $sql = "INSERT INTO travels (
                       user_id, departure_city, arrival_city, departure_date,
                       departure_time, available_seats, price_per_seat, description, total_seats, car_details
                   ) VALUES (
                       :user_id, :departure_city, :arrival_city, :departure_date,
                       :departure_time, :available_seats, :price_per_seat, :description, :total_seats, :car_details
                   )";
        } else {
            $sql = "INSERT INTO travels (
                       user_id, departure_city, arrival_city, departure_date,
                       departure_time, available_seats, price_per_seat, description, total_seats
                   ) VALUES (
                       :user_id, :departure_city, :arrival_city, :departure_date,
                       :departure_time, :available_seats, :price_per_seat, :description, :total_seats
                   )";
        }

        try {
            $stmt = $this->pdo->prepare($sql);

            $params = [
                ':user_id'          => $userId,
                ':departure_city'   => $data['depart'],
                ':arrival_city'     => $data['arrivee'],
                ':departure_date'   => $data['date_depart'],
                ':departure_time'   => $data['heure_depart'],
                ':available_seats'  => $data['seats'],
                ':price_per_seat'   => $data['price'],
                ':description'      => $data['description'],
                ':total_seats'      => $data['seats']
            ];
            if ($hasCarDetails) {
                $params[':car_details'] = $data['car_details'] ?? null;
            }
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("DB Error in createTravelWithOptionalCarDetails: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les trajets avec le nom du conducteur.
     * @return array Un tableau de tous les trajets.
     */
    public function getAllTravels(): array {
        $sql = $this->getBaseSelectQuery() . "
           ORDER BY
               T.departure_date ASC, T.departure_time ASC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { // Utilisation de la classe complète \PDOException
            error_log("SQL Error in getAllTravels: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Recherche des trajets en fonction des critères fournis (ville/date).
     * @param string|null $departure Ville de départ.
     * @param string|null $arrival Ville d'arrivée.
     * @param string|null $date Date de départ.
     * @return array Un tableau des trajets correspondants.
     */
    public function searchTravels(?string $departure, ?string $arrival, ?string $date): array {
        $sql = $this->getBaseSelectQuery();
        $conditions = [];
        $params = [];

        // Utilisation de marqueurs nommés pour la recherche pour la cohérence
        if (!empty($departure)) {
            $conditions[] = "T.departure_city LIKE :departure";
            $params[':departure'] = '%' . $departure . '%';
        }
        if (!empty($arrival)) {
            $conditions[] = "T.arrival_city LIKE :arrival";
            $params[':arrival'] = '%' . $arrival . '%';
        }
        if (!empty($date)) {
            $conditions[] = "T.departure_date = :date";
            $params[':date'] = $date;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY T.departure_date ASC, T.departure_time ASC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { // Utilisation de la classe complète \PDOException
            error_log("SQL Error in searchTravels: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Récupère un seul trajet par son ID, incluant les infos du conducteur.
     * @param int $id L'ID du trajet à récupérer.
     * @return array|null Le tableau associatif du trajet, ou null si non trouvé.
     */
    public function getTravelById(int $id): ?array {
        $sql = $this->getBaseSelectQuery() . "
               WHERE
                   T.id = :id"; // Utilisation de marqueurs nommés
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT); // Bind par nom
            $stmt->execute();
            $travel = $stmt->fetch(PDO::FETCH_ASSOC);
            return $travel ?: null;
        } catch (PDOException $e) { // Utilisation de la classe complète \PDOException
            error_log("SQL Error in getTravelById: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Récupère tous les détails d'un trajet, y compris les informations de contact du conducteur.
     * (Utilisé par detail_trajet.php)
     * @param int $travelId L'ID du trajet à récupérer.
     * @return array|null Les détails du trajet et du conducteur, ou null si non trouvé.
     */
    public function getTravelContactDetails(int $travelId): ?array {
        // Colonnes spécifiques pour les contacts (email, phone_number)
        $columns = 'T.*, U.first_name, U.last_name, U.email, U.phone_number';
        $sql = $this->getBaseSelectQuery($columns) . "
        WHERE
           T.id = :id
       ";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $travelId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { // Utilisation de la classe complète \PDOException
            error_log("DB Error in getTravelContactDetails: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Récupère tous les trajets proposés par un utilisateur spécifique.
     * (Utilisé par profil.php)
     * @param int $userId L'ID de l'utilisateur.
     * @return array Une liste des trajets.
     */
    public function getUserTravels(int $userId): array {
        $sql = "
       SELECT
           id,
           departure_city,
           arrival_city,
           departure_date,
           departure_time,
           price_per_seat,
           available_seats,
           COALESCE(total_seats, available_seats) AS total_seats
       FROM
           travels
       WHERE
           user_id = :userId
       ORDER BY
           departure_date DESC, departure_time DESC
       ";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { // Utilisation de la classe complète \PDOException
            error_log("DB Error in getUserTravels: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Met à jour un trajet existant si l'utilisateur en est le propriétaire.
     * @param int $travelId ID du trajet à mettre à jour.
     * @param int $userId ID de l'utilisateur (propriétaire attendu).
     * @param array $data Données validées (mêmes clés que pour createTravel).
     * @return bool True si la mise à jour a affecté au moins une ligne, sinon False.
     */
    public function updateTravel(int $travelId, int $userId, array $data): bool {
        $sql = "UPDATE travels SET
                    departure_city = :departure_city,
                    arrival_city = :arrival_city,
                    departure_date = :departure_date,
                    departure_time = :departure_time,
                    available_seats = :available_seats,
                    price_per_seat = :price_per_seat,
                    description = :description,
                    total_seats = :total_seats
                WHERE id = :id AND user_id = :user_id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute([
                ':departure_city'  => $data['depart'],
                ':arrival_city'    => $data['arrivee'],
                ':departure_date'  => $data['date_depart'],
                ':departure_time'  => $data['heure_depart'],
                ':available_seats' => $data['seats'],
                ':price_per_seat'  => $data['price'],
                ':description'     => $data['description'] ?? '',
                ':total_seats'     => $data['seats'],
                ':id'              => $travelId,
                ':user_id'         => $userId,
            ]);
            return $ok && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("DB Error in updateTravel: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update travel (optional car_details support if column exists).
     */
    public function updateTravelWithOptionalCarDetails(int $travelId, int $userId, array $data): bool {
        $hasCar = $this->hasColumn('travels', 'car_details');
        if ($hasCar) {
            $sql = "UPDATE travels SET
                        departure_city = :departure_city,
                        arrival_city = :arrival_city,
                        departure_date = :departure_date,
                        departure_time = :departure_time,
                        available_seats = :available_seats,
                        price_per_seat = :price_per_seat,
                        description = :description,
                        total_seats = :total_seats,
                        car_details = :car_details
                    WHERE id = :id AND user_id = :user_id";
        } else {
            $sql = "UPDATE travels SET
                        departure_city = :departure_city,
                        arrival_city = :arrival_city,
                        departure_date = :departure_date,
                        departure_time = :departure_time,
                        available_seats = :available_seats,
                        price_per_seat = :price_per_seat,
                        description = :description,
                        total_seats = :total_seats
                    WHERE id = :id AND user_id = :user_id";
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $params = [
                ':departure_city'  => $data['depart'],
                ':arrival_city'    => $data['arrivee'],
                ':departure_date'  => $data['date_depart'],
                ':departure_time'  => $data['heure_depart'],
                ':available_seats' => $data['seats'],
                ':price_per_seat'  => $data['price'],
                ':description'     => $data['description'] ?? '',
                ':total_seats'     => $data['seats'],
                ':id'              => $travelId,
                ':user_id'         => $userId,
            ];
            if ($hasCar) {
                $params[':car_details'] = $data['car_details'] ?? null;
            }
            $ok = $stmt->execute($params);
            return $ok && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("DB Error in updateTravelWithOptionalCarDetails: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Supprime un trajet si l'utilisateur spécifié en est le propriétaire.
     * (Vérification de sécurité par user_id)
     * @param int $travelId L'ID du trajet à supprimer.
     * @param int $userId L'ID de l'utilisateur effectuant la suppression.
     * @return bool True si le trajet a été supprimé, False sinon.
     */
    public function deleteTravel(int $travelId, int $userId): bool {
        $deleteSql = "DELETE FROM travels WHERE id = :id AND user_id = :userId";
        try {
            $stmt = $this->pdo->prepare($deleteSql);
            $stmt->bindParam(':id', $travelId, PDO::PARAM_INT);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) { // Utilisation de la classe complète \PDOException
            error_log("DB Error in deleteTravel: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks if a column exists in the current schema for a given table.
     */
    private function hasColumn(string $table, string $column): bool {
        try {
            $sql = "SELECT COUNT(*) AS cnt
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':t' => $table, ':c' => $column]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($row && (int)$row['cnt'] > 0);
        } catch (PDOException $e) {
            error_log("Schema check failed in hasColumn: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reserve seats for a user on a given travel.
     * Decrements available_seats atomically and creates/updates a reservation.
     * Returns true on success, false otherwise.
     */
    public function reserveSeats(int $travelId, int $userId, int $seats = 1): bool {
        if ($seats <= 0) {
            return false;
        }
        try {
            $this->pdo->beginTransaction();

            // Lock the travel row to avoid race conditions
            $lockSql = "SELECT user_id, available_seats FROM travels WHERE id = :id FOR UPDATE";
            $lockStmt = $this->pdo->prepare($lockSql);
            $lockStmt->execute([':id' => $travelId]);
            $travel = $lockStmt->fetch(PDO::FETCH_ASSOC);
            if (!$travel) {
                $this->pdo->rollBack();
                return false;
            }

            // Prevent driver from booking own travel
            if ((int)$travel['user_id'] === (int)$userId) {
                $this->pdo->rollBack();
                return false;
            }

            // For single-reservation policy: block if an active reservation already exists
            $chkSql = "SELECT id FROM reservations WHERE travel_id = :t AND user_id = :u AND status IN ('pending','confirmed') LIMIT 1 FOR UPDATE";
            $chk = $this->pdo->prepare($chkSql);
            $chk->execute([':t' => $travelId, ':u' => $userId]);
            if ($chk->fetch(PDO::FETCH_ASSOC)) {
                $this->pdo->rollBack();
                return false;
            }

            $available = (int)$travel['available_seats'];
            if ($available < $seats) {
                $this->pdo->rollBack();
                return false;
            }

            // Insert reservation or reactivate a previously cancelled one
            $resSql = "INSERT INTO reservations (travel_id, user_id, seats_booked, status)
                       VALUES (:travel_id, :user_id, :seats_booked, 'confirmed')
                       ON DUPLICATE KEY UPDATE seats_booked = VALUES(seats_booked), status = 'confirmed', booking_date = CURRENT_TIMESTAMP";
            $resStmt = $this->pdo->prepare($resSql);
            $resStmt->execute([
                ':travel_id'   => $travelId,
                ':user_id'     => $userId,
                ':seats_booked'=> $seats,
            ]);

            // Decrement available seats
            $updSql = "UPDATE travels SET available_seats = available_seats - :seats WHERE id = :id";
            $updStmt = $this->pdo->prepare($updSql);
            $updStmt->execute([':seats' => $seats, ':id' => $travelId]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            try { $this->pdo->rollBack(); } catch (Throwable $ignored) {}
            error_log("Reservation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recherche des trajets pour des villes données autour d'une date cible (+/- N jours).
     */
    public function searchTravelsNearDate(?string $departure, ?string $arrival, string $date, int $toleranceDays = 3): array {
        $toleranceDays = max(1, min(30, (int)$toleranceDays));
        try { $center = new DateTime($date); } catch (Exception $e) { return []; }
        $start = (clone $center)->modify('-' . $toleranceDays . ' day')->format('Y-m-d');
        $end   = (clone $center)->modify('+' . $toleranceDays . ' day')->format('Y-m-d');

        $sql = $this->getBaseSelectQuery();
        $conditions = [];
        $params = [':start_date' => $start, ':end_date' => $end];
        if (!empty($departure)) {
            $conditions[] = "T.departure_city LIKE :departure";
            $params[':departure'] = '%' . $departure . '%';
        }
        if (!empty($arrival)) {
            $conditions[] = "T.arrival_city LIKE :arrival";
            $params[':arrival'] = '%' . $arrival . '%';
        }
        $conditions[] = "T.departure_date BETWEEN :start_date AND :end_date";
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY T.departure_date ASC, T.departure_time ASC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SQL Error in searchTravelsNearDate: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update seats booked for an existing reservation; can both increase or decrease.
     * If newSeats <= 0, cancels the reservation and restores seats.
     */
    public function updateReservationSeats(int $travelId, int $userId, int $newSeats): bool {
        try {
            $this->pdo->beginTransaction();

            // Lock travel
            $tStmt = $this->pdo->prepare("SELECT total_seats, available_seats FROM travels WHERE id = :id FOR UPDATE");
            $tStmt->execute([':id' => $travelId]);
            $t = $tStmt->fetch(PDO::FETCH_ASSOC);
            if (!$t) { $this->pdo->rollBack(); return false; }

            // Lock reservation
            $rStmt = $this->pdo->prepare("SELECT id, seats_booked, status FROM reservations WHERE travel_id = :t AND user_id = :u LIMIT 1 FOR UPDATE");
            $rStmt->execute([':t' => $travelId, ':u' => $userId]);
            $r = $rStmt->fetch(PDO::FETCH_ASSOC);
            if (!$r || !in_array($r['status'], ['pending','confirmed'], true)) {
                $this->pdo->rollBack();
                return false;
            }

            $current = (int)$r['seats_booked'];
            $totalSeats = (int)$t['total_seats'];
            $available = (int)$t['available_seats'];

            if ($newSeats <= 0) {
                // Cancel reservation entirely
                $updRes = $this->pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = :id");
                $updRes->execute([':id' => $r['id']]);
                $updSeats = $this->pdo->prepare("UPDATE travels SET available_seats = LEAST(:total, available_seats + :s) WHERE id = :id");
                $updSeats->execute([':total' => $totalSeats, ':s' => $current, ':id' => $travelId]);
                $this->pdo->commit();
                return true;
            }

            $delta = $newSeats - $current;
            if ($delta > 0) {
                if ($available < $delta) { $this->pdo->rollBack(); return false; }
                $resUpd = $this->pdo->prepare("UPDATE reservations SET seats_booked = :s WHERE id = :id");
                $resUpd->execute([':s' => $newSeats, ':id' => $r['id']]);
                $dec = $this->pdo->prepare("UPDATE travels SET available_seats = available_seats - :d WHERE id = :id");
                $dec->execute([':d' => $delta, ':id' => $travelId]);
            } elseif ($delta < 0) {
                $resUpd = $this->pdo->prepare("UPDATE reservations SET seats_booked = :s WHERE id = :id");
                $resUpd->execute([':s' => $newSeats, ':id' => $r['id']]);
                $inc = $this->pdo->prepare("UPDATE travels SET available_seats = LEAST(:total, available_seats + :d) WHERE id = :id");
                $inc->execute([':total' => $totalSeats, ':d' => abs($delta), ':id' => $travelId]);
            } // if delta == 0, nothing to do

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            try { $this->pdo->rollBack(); } catch (Throwable $ignored) {}
            error_log("updateReservationSeats failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Returns an active reservation row for a user on a travel (or null).
     */
    public function getReservationForUser(int $travelId, int $userId): ?array {
        try {
            $sql = "SELECT * FROM reservations WHERE travel_id = :t AND user_id = :u AND status IN ('pending','confirmed') LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':t' => $travelId, ':u' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("getReservationForUser failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Returns true if a user already has an active reservation for a travel.
     */
    public function hasActiveReservation(int $travelId, int $userId): bool {
        try {
            $sql = "SELECT COUNT(*) AS cnt FROM reservations WHERE travel_id = :t AND user_id = :u AND status IN ('pending','confirmed')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':t' => $travelId, ':u' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && (int)$row['cnt'] > 0;
        } catch (PDOException $e) {
            error_log("hasActiveReservation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel a reservation for a user and restore available seats.
     */
    public function cancelReservation(int $travelId, int $userId): bool {
        try {
            $this->pdo->beginTransaction();
            // Lock the reservation row
            $sel = $this->pdo->prepare("SELECT id, seats_booked FROM reservations WHERE travel_id = :t AND user_id = :u AND status IN ('pending','confirmed') LIMIT 1 FOR UPDATE");
            $sel->execute([':t' => $travelId, ':u' => $userId]);
            $res = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$res) {
                $this->pdo->rollBack();
                return false;
            }
            $seats = (int)$res['seats_booked'];

            // Update reservation status
            $updRes = $this->pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = :id");
            $updRes->execute([':id' => $res['id']]);

            // Restore seats, capped by total_seats
            $updSeats = $this->pdo->prepare("UPDATE travels SET available_seats = LEAST(total_seats, available_seats + :s) WHERE id = :id");
            $updSeats->execute([':s' => $seats, ':id' => $travelId]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            try { $this->pdo->rollBack(); } catch (Throwable $ignored) {}
            error_log("cancelReservation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Returns active reservations for a user with joined travel and driver info.
     */
    public function getUserReservations(int $userId): array {
        $sql = "SELECT 
                    r.travel_id,
                    r.seats_booked,
                    r.status,
                    r.booking_date,
                    T.departure_city,
                    T.arrival_city,
                    T.departure_date,
                    T.departure_time,
                    T.price_per_seat,
                    T.available_seats,
                    COALESCE(T.total_seats, T.available_seats) AS total_seats,
                    U.first_name, U.last_name
                FROM reservations r
                JOIN travels T ON r.travel_id = T.id
                JOIN users U ON T.user_id = U.id
                WHERE r.user_id = :uid AND r.status IN ('pending','confirmed')
                ORDER BY T.departure_date ASC, T.departure_time ASC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getUserReservations failed: " . $e->getMessage());
            return [];
        }
    }
}
