<?php
// Fichier: src/TravelManager.php - 20/11/2025 - 13:15

require_once __DIR__ . '/CreditManager.php';
require_once __DIR__ . '/SystemParameterManager.php';
require_once __DIR__ . '/NoSqlLogger.php';
require_once __DIR__ . '/Email.php';

/**
 * Gère les opérations de base de données liées aux trajets (covoiturages).
 */
class TravelManager {
    // Utilisation de la classe complète \PDO
    private PDO $pdo;
    private const PLATFORM_FEE = 2;

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
     * Builds a column list including optional user photo columns if present in schema.
     */
    private function getUserColumnsWithPhoto(): string
    {
        $cols = 'T.*, U.first_name, U.last_name';
        if ($this->hasColumn('users', 'photo')) {
            $cols .= ', U.photo AS user_photo_bin';
        }
        if ($this->hasColumn('users', 'photo_mime_type')) {
            $cols .= ', U.photo_mime_type AS user_photo_mime';
        }
        if ($this->hasColumn('users', 'photo_path')) {
            $cols .= ', U.photo_path AS user_photo_path';
        }
        return $cols;
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
        $sql = $this->getBaseSelectQuery($this->getUserColumnsWithPhoto()) . "
           WHERE TIMESTAMP(T.departure_date, COALESCE(T.departure_time,'23:59:59')) >= NOW()
             AND T.available_seats > 0
             AND (T.status IS NULL OR T.status IN ('planned','open'))
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
    public function searchTravels(?string $departure, ?string $arrival, ?string $date, ?bool $ecoOnly = null, ?float $maxPrice = null, ?float $maxDuration = null, ?float $minRating = null): array {
        // Base select plus optional aggregates (average rating)
        $cols = $this->getUserColumnsWithPhoto() . ', R.avg_rating';
        $sql = $this->getBaseSelectQuery($cols);
        $conditions = [];
        $params = [];

        // Optional joins for filters
        $joins = [];
        if ($ecoOnly === true) {
            $joins[] = "LEFT JOIN vehicles V ON V.id = T.vehicle_id";
            $conditions[] = "V.energy = 'electrique'";
        }
        // Join reviews average for min rating filter
        if ($minRating !== null) {
            $joins[] = "LEFT JOIN (
                SELECT reviewed_user_id, AVG(rating) AS avg_rating
                FROM reviews
                WHERE status = 'published'
                GROUP BY reviewed_user_id
            ) R ON R.reviewed_user_id = U.id";
            $conditions[] = "R.avg_rating >= :min_rating";
            $params[':min_rating'] = $minRating;
        } else {
            // Still expose rating column if joined not requested
            $joins[] = "LEFT JOIN (
                SELECT reviewed_user_id, AVG(rating) AS avg_rating
                FROM reviews
                WHERE status = 'published'
                GROUP BY reviewed_user_id
            ) R ON R.reviewed_user_id = U.id";
        }

        if (!empty($joins)) {
            $sql .= " " . implode(" ", $joins);
        }

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
        if ($maxPrice !== null) {
            $conditions[] = "T.price_per_seat <= :max_price";
            $params[':max_price'] = $maxPrice;
        }
        // Note: maxDuration filter not applied because no duration/arrival fields exist in schema.

        // Always hide past travels and full trips in search results
        $futureOnly = "TIMESTAMP(T.departure_date, COALESCE(T.departure_time,'23:59:59')) >= NOW()";
        $seatsOnly  = "T.available_seats > 0";
        $statusOnly = "(T.status IS NULL OR T.status IN ('planned','open'))";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions) . " AND " . $futureOnly . " AND " . $seatsOnly . " AND " . $statusOnly;
        } else {
            $sql .= " WHERE " . $futureOnly . " AND " . $seatsOnly . " AND " . $statusOnly;
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
        $sql = $this->getBaseSelectQuery($this->getUserColumnsWithPhoto()) . "
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
        // Colonnes spécifiques pour les contacts (email, phone_number) + photo si dispo
        $columns = 'T.*, U.first_name, U.last_name, U.email, U.phone_number';
        if ($this->hasColumn('users', 'photo')) { $columns .= ', U.photo AS user_photo_bin'; }
        if ($this->hasColumn('users', 'photo_mime_type')) { $columns .= ', U.photo_mime_type AS user_photo_mime'; }
        if ($this->hasColumn('users', 'photo_path')) { $columns .= ', U.photo_path AS user_photo_path'; }
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
           COALESCE(total_seats, available_seats) AS total_seats,
           earnings,
           COALESCE(status,'planned') AS status
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

            $lockSql = "SELECT user_id, available_seats, price_per_seat, departure_date, departure_time, status
                        FROM travels WHERE id = :id FOR UPDATE";
            $lockStmt = $this->pdo->prepare($lockSql);
            $lockStmt->execute([':id' => $travelId]);
            $travel = $lockStmt->fetch(PDO::FETCH_ASSOC);
            if (!$travel) {
                $this->pdo->rollBack();
                return false;
            }

            // Prevent booking on past travels or non-planned status
            $depDate = $travel['departure_date'] ?? null;
            $depTime = $travel['departure_time'] ?? null;
            if ($depDate) {
                $when = $depDate . ' ' . ($depTime ?: '00:00:00');
                try { $dt = new DateTime($when); } catch (Throwable $e) { $dt = null; }
                if ($dt && $dt < new DateTime()) { $this->pdo->rollBack(); return false; }
            }
            if (!empty($travel['status']) && !in_array($travel['status'], ['planned','open'], true)) { $this->pdo->rollBack(); return false; }

            if ((int)$travel['user_id'] === (int)$userId) {
                $this->pdo->rollBack();
                return false;
            }

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

            $seatCost = (float)$travel['price_per_seat'] * $seats;
            $seatCostCredits = (int)ceil($seatCost);
            $driverId = (int)$travel['user_id'];
            $driverGain = max(0, $seatCostCredits - self::PLATFORM_FEE);

            $creditManager = new CreditManager($this->pdo);
            if (!$creditManager->hasSufficientBalance($userId, $seatCostCredits)) {
                $this->pdo->rollBack();
                return false;
            }

            $resSql = "INSERT INTO reservations
                        (travel_id, user_id, seats_booked, status, booking_date, confirmed_at, credit_spent, driver_credit)
                       VALUES
                        (:travel_id, :user_id, :seats_booked, 'pending', CURRENT_TIMESTAMP, NULL, 0, 0)";
            $resStmt = $this->pdo->prepare($resSql);
            $resStmt->execute([
                ':travel_id'    => $travelId,
                ':user_id'      => $userId,
                ':seats_booked' => $seats,
            ]);
            $reservationId = (int)$this->pdo->lastInsertId();

            // Pending-first flow committed; optional auto-confirm
            $this->pdo->commit();

            // Log to NoSQL (reservation event). Determine status without relying on $auto variable scope.
            try {
                $autoNow = '1';
                try { $autoNow = (string)(new SystemParameterManager($this->pdo))->get('booking_auto_confirm', '1'); } catch (\Throwable $ignored) {}
                $logStatus = ($autoNow === '1' || strtolower($autoNow) === 'true') ? 'confirmed' : 'pending';
                NoSqlLogger::log('reservations', [
                    'event' => 'reserve',
                    'status' => $logStatus,
                    'travel_id' => $travelId,
                    'user_id' => $userId,
                    'seats' => $seats
                ]);
            } catch (\Throwable $e) {}
            $param = new SystemParameterManager($this->pdo);
            $auto = (string)$param->get('booking_auto_confirm', '1');
            if ($auto === '1' || strtolower($auto) === 'true') {
                return $this->confirmReservation($travelId, $userId);
            }
            return true;

            $updSql = "UPDATE travels
                       SET available_seats = available_seats - :seats,
                           earnings = earnings + :gain
                       WHERE id = :id";
            /* CLEANED: unreachable legacy block below (pre pending-first refactor)
            $updStmt = $this->pdo->prepare($updSql);
            $updStmt->execute([
                ':seats' => $seats,
                ':gain'  => $driverGain,
                ':id'    => $travelId
            ]);

            if (!$creditManager->adjustBalance($userId, -$seatCostCredits, 'reservation_debit', $reservationId, "Réservation trajet #{$travelId}")) {
                $this->pdo->rollBack();
                return false;
            }

            if ($driverGain > 0) {
                if (!$creditManager->adjustBalance($driverId, $driverGain, 'reservation_credit', $reservationId, "Gain trajet #{$travelId}")) {
                    $this->pdo->rollBack();
                    return false;
                }
            }

            $this->pdo->commit();

            try { NoSqlLogger::log('reservations', ['event'=>'confirm','travel_id'=>$travelId,'user_id'=>$userId,'seats'=>$seats]); } catch (\Throwable $e) {}
            return true;
            */
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

        $sql = $this->getBaseSelectQuery($this->getUserColumnsWithPhoto());
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
        $conditions[] = "TIMESTAMP(T.departure_date, COALESCE(T.departure_time,'23:59:59')) >= NOW()";
        $conditions[] = "T.available_seats > 0";
        $conditions[] = "(T.status IS NULL OR T.status IN ('planned','open'))";
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
            $sel = $this->pdo->prepare("SELECT r.id, r.seats_booked, r.status, r.credit_spent, r.driver_credit, t.user_id AS driver_id
                                         FROM reservations r
                                         JOIN travels t ON t.id = r.travel_id
                                         WHERE r.travel_id = :t AND r.user_id = :u AND r.status IN ('pending','confirmed')
                                         LIMIT 1 FOR UPDATE");
            $sel->execute([':t' => $travelId, ':u' => $userId]);
            $res = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$res) {
                $this->pdo->rollBack();
                return false;
            }

            $seats = (int)$res['seats_booked'];
            $status = (string)$res['status'];
            $creditSpent = (int)$res['credit_spent'];
            $driverCredit = (int)$res['driver_credit'];
            $driverId = (int)$res['driver_id'];

            $updRes = $this->pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = :id");
            $updRes->execute([':id' => $res['id']]);

            if ($status === 'confirmed') {
                $updSeats = $this->pdo->prepare("UPDATE travels
                                                 SET available_seats = LEAST(total_seats, available_seats + :s),
                                                     earnings = GREATEST(0, earnings - :driver_credit)
                                                 WHERE id = :id");
                $updSeats->execute([':s' => $seats, ':driver_credit' => $driverCredit, ':id' => $travelId]);

                $creditManager = new CreditManager($this->pdo);
                if ($creditSpent > 0) {
                    if (!$creditManager->adjustBalance($userId, $creditSpent, 'reservation_refund', (int)$res['id'], "Annulation trajet #{$travelId}")) {
                        $this->pdo->rollBack();
                        return false;
                    }
                }

                if ($driverCredit > 0) {
                    if (!$creditManager->adjustBalance($driverId, -$driverCredit, 'driver_refund', (int)$res['id'], "Annulation trajet #{$travelId}")) {
                        $this->pdo->rollBack();
                        return false;
                    }
                }
            }

            $this->pdo->commit();

            // Notify passenger and driver (stubbed email logging)
            try {
                // Fetch emails
                $uStmt = $this->pdo->prepare("SELECT email FROM users WHERE id = :id");
                $uStmt->execute([':id' => $userId]);
                $passengerEmail = ($uStmt->fetch(PDO::FETCH_ASSOC)['email'] ?? null);
                $uStmt->execute([':id' => $driverId]);
                $driverEmail = ($uStmt->fetch(PDO::FETCH_ASSOC)['email'] ?? null);
                if ($passengerEmail) {
                    Email::send($passengerEmail, 'Annulation de votre réservation', "Votre réservation pour le trajet #{$travelId} a été annulée.");
                }
                if ($driverEmail) {
                    Email::send($driverEmail, 'Réservation annulée (passager)', "Une réservation (trajet #{$travelId}) a été annulée par le passager.");
                }
            } catch (Throwable $logErr) { /* ignore logging failures */ }

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
                    r.credit_spent,
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

    /**
     * Change travel status (planned -> in_progress -> completed) for driver's own travel.
     * On completion, notifies passengers via email log.
     */
    public function setTravelStatus(int $travelId, int $driverId, string $newStatus): bool
    {
        $newStatus = in_array($newStatus, ['in_progress', 'completed'], true) ? $newStatus : '';
        if ($newStatus === '') { return false; }
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("SELECT status FROM travels WHERE id = :id AND user_id = :uid FOR UPDATE");
            $stmt->execute([':id' => $travelId, ':uid' => $driverId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { $this->pdo->rollBack(); return false; }
            $current = $row['status'] ?? 'planned';
            if ($newStatus === 'in_progress' && $current !== 'planned') { $this->pdo->rollBack(); return false; }
            if ($newStatus === 'completed' && $current !== 'in_progress') { $this->pdo->rollBack(); return false; }

            $upd = $this->pdo->prepare("UPDATE travels SET status = :s WHERE id = :id");
            $upd->execute([':s' => $newStatus, ':id' => $travelId]);
            $this->pdo->commit();

            if ($newStatus === 'completed') {
                // Notify all confirmed passengers (stubbed email log)
                try {
                    $q = $this->pdo->prepare("SELECT u.email FROM reservations r JOIN users u ON u.id = r.user_id WHERE r.travel_id = :t AND r.status = 'confirmed'");
                    $q->execute([':t' => $travelId]);
                    $emails = $q->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($emails as $mail) {
                        if ($mail) { Email::send($mail, 'Trajet terminé', "Votre trajet #{$travelId} est marqué comme terminé. Merci de laisser un avis."); }
                    }
                    // Notify driver for record
                    $driverMail = $this->pdo->query("SELECT email FROM users WHERE id = " . (int)$driverId)->fetchColumn();
                    if ($driverMail) { Email::send($driverMail, 'Trajet clôturé', "Trajet #{$travelId} clôturé. Revenus mis à jour."); }
                } catch (Throwable $ignored) { }
                try { NoSqlLogger::log('travels', ['event'=>'complete','travel_id'=>$travelId,'driver_id'=>$driverId]); } catch (\Throwable $e) {}
            }
            return true;
        } catch (Throwable $e) {
            try { $this->pdo->rollBack(); } catch (Throwable $ignored2) {}
            error_log('setTravelStatus failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Liste les réservations passées pour lesquelles l'utilisateur n'a pas encore laissé d'avis.
     */
    public function getPendingReviewsForPassenger(int $userId): array {
        $sql = "SELECT
                    r.travel_id,
                    T.departure_city,
                    T.arrival_city,
                    T.departure_date,
                    T.departure_time,
                    U.id AS driver_id,
                    U.first_name AS driver_first_name,
                    U.last_name AS driver_last_name
                FROM reservations r
                JOIN travels T ON r.travel_id = T.id
                JOIN users U ON T.user_id = U.id
                LEFT JOIN reviews rev
                    ON rev.travel_id = T.id
                   AND rev.reviewer_id = :reviewer_id
                WHERE r.user_id = :res_user_id
                  AND r.status = 'confirmed'
                  AND rev.id IS NULL
                  AND TIMESTAMP(T.departure_date, COALESCE(T.departure_time, '00:00:00')) < NOW()
                ORDER BY T.departure_date DESC, T.departure_time DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':reviewer_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':res_user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rows;
        } catch (PDOException $e) {
            error_log("getPendingReviewsForPassenger failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les informations nécessaires pour laisser un avis sur un trajet donné.
     */
    public function getReviewContext(int $travelId, int $userId): ?array {
        $sql = "SELECT
                    r.travel_id,
                    T.departure_city,
                    T.arrival_city,
                    T.departure_date,
                    T.departure_time,
                    U.id AS driver_id,
                    U.first_name AS driver_first_name,
                    U.last_name AS driver_last_name
                FROM reservations r
                JOIN travels T ON r.travel_id = T.id
                JOIN users U ON T.user_id = U.id
                LEFT JOIN reviews rev
                    ON rev.travel_id = T.id
                   AND rev.reviewer_id = :reviewer_id
                WHERE r.travel_id = :travel_id
                  AND r.user_id = :res_user_id
                  AND r.status = 'confirmed'
                  AND rev.id IS NULL
                  AND TIMESTAMP(T.departure_date, COALESCE(T.departure_time, '00:00:00')) < NOW()
                LIMIT 1";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':travel_id' => $travelId,
                ':res_user_id' => $userId,
                ':reviewer_id' => $userId,
            ]);
            $context = $stmt->fetch(PDO::FETCH_ASSOC);
            return $context ?: null;
        } catch (PDOException $e) {
            error_log("getReviewContext failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Returns pending reservations for travels owned by the given driver.
     */
    public function getPendingReservationsForDriver(int $driverId): array
    {
        // Build optional passenger photo columns if present
        $uCols = 'u.first_name AS passenger_first_name, u.last_name AS passenger_last_name, u.email AS passenger_email';
        if ($this->hasColumn('users', 'photo')) { $uCols .= ', u.photo AS passenger_photo_bin'; }
        if ($this->hasColumn('users', 'photo_mime_type')) { $uCols .= ', u.photo_mime_type AS passenger_photo_mime'; }
        if ($this->hasColumn('users', 'photo_path')) { $uCols .= ', u.photo_path AS passenger_photo_path'; }

        $sql = "SELECT 
                    r.id              AS reservation_id,
                    r.travel_id,
                    r.user_id         AS passenger_id,
                    r.seats_booked,
                    r.booking_date,
                    t.departure_city,
                    t.arrival_city,
                    t.departure_date,
                    t.departure_time,
                    {$uCols}
                FROM reservations r
                JOIN travels t   ON t.id = r.travel_id
                JOIN users u     ON u.id = r.user_id
                WHERE t.user_id = :driver_id AND r.status = 'pending'
                ORDER BY r.booking_date DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':driver_id' => $driverId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getPendingReservationsForDriver failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Confirms a pending reservation and applies seats/credits/earnings atomically.
     */
    public function confirmReservation(int $travelId, int $userId): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Lock travel and read current pricing/availability
            $tStmt = $this->pdo->prepare("SELECT user_id, available_seats, price_per_seat, departure_date, departure_time, status FROM travels WHERE id = :id FOR UPDATE");
            $tStmt->execute([':id' => $travelId]);
            $t = $tStmt->fetch(PDO::FETCH_ASSOC);
            if (!$t) { $this->pdo->rollBack(); return false; }

            // Prevent confirming on past travels or non-planned status
            $depDate = $t['departure_date'] ?? null;
            $depTime = $t['departure_time'] ?? null;
            if ($depDate) {
                $when = $depDate . ' ' . ($depTime ?: '00:00:00');
                try { $dt = new DateTime($when); } catch (Throwable $e) { $dt = null; }
                if ($dt && $dt < new DateTime()) { $this->pdo->rollBack(); return false; }
            }
            if (!empty($t['status']) && !in_array($t['status'], ['planned','open'], true)) { $this->pdo->rollBack(); return false; }

            // Lock pending reservation
            $rStmt = $this->pdo->prepare("SELECT id, seats_booked FROM reservations WHERE travel_id = :t AND user_id = :u AND status = 'pending' LIMIT 1 FOR UPDATE");
            $rStmt->execute([':t' => $travelId, ':u' => $userId]);
            $r = $rStmt->fetch(PDO::FETCH_ASSOC);
            if (!$r) { $this->pdo->rollBack(); return false; }

            $seats = (int)$r['seats_booked'];
            $available = (int)$t['available_seats'];
            if ($available < $seats) { $this->pdo->rollBack(); return false; }

            $seatCostCredits = (int)ceil(((float)$t['price_per_seat']) * $seats);
            $driverId = (int)$t['user_id'];
            $driverGain = max(0, $seatCostCredits - self::PLATFORM_FEE);

            $creditManager = new CreditManager($this->pdo);
            if (!$creditManager->hasSufficientBalance($userId, $seatCostCredits)) { $this->pdo->rollBack(); return false; }

            // Flip to confirmed and record amounts
            $updRes = $this->pdo->prepare("UPDATE reservations SET status = 'confirmed', confirmed_at = CURRENT_TIMESTAMP, credit_spent = :cs, driver_credit = :dg WHERE id = :id");
            $updRes->execute([':cs' => $seatCostCredits, ':dg' => $driverGain, ':id' => $r['id']]);

            // Apply travel side-effects
            $updTravel = $this->pdo->prepare("UPDATE travels SET available_seats = available_seats - :s, earnings = earnings + :gain WHERE id = :id");
            $updTravel->execute([':s' => $seats, ':gain' => $driverGain, ':id' => $travelId]);

            // Apply credit movements
            if (!$creditManager->adjustBalance($userId, -$seatCostCredits, 'reservation_debit', (int)$r['id'], "Réservation trajet #{$travelId}")) { $this->pdo->rollBack(); return false; }
            if ($driverGain > 0) {
                if (!$creditManager->adjustBalance($driverId, $driverGain, 'reservation_credit', (int)$r['id'], "Gain trajet #{$travelId}")) { $this->pdo->rollBack(); return false; }
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            try { $this->pdo->rollBack(); } catch (Throwable $ignored) {}
            error_log("confirmReservation failed: " . $e->getMessage());
            return false;
        }
    }
}
