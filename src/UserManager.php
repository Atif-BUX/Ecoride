<?php
// Fichier: src/UserManager.php

// Assurez-vous que le chemin vers Database.php est correct
require_once 'Database.php';

class UserManager {

    private PDO $pdo; // Propriété pour stocker l'objet PDO
    private array $columnCache = [];

    // Constructeur : reçoit l'objet PDO lors de l'instanciation
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Crée un hash sécurisé pour un mot de passe (méthode statique utilitaire).
     * @param string $password
     * @return string Le hash du mot de passe.
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Récupère les données d'un utilisateur par son email (utilisé en interne).
     * @param string $email
     * @return array|null Les données utilisateur (y compris le hash) ou null si non trouvé.
     */
    private function getUserByEmail(string $email): ?array {
        // Utilisation de $this->pdo
        $sql = "SELECT id, email, password, first_name, last_name, is_active FROM users WHERE email = :email";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {
            error_log("DB Error in getUserByEmail: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retourne les informations d'un utilisateur (pour l'affichage du profil).
     * Seules les colonnes existantes sont sélectionnées pour éviter les erreurs SQL.
     */
    public function getUserProfile(int $id): ?array {
        $baseColumns = ['id', 'first_name', 'last_name', 'email', 'credit_balance'];
        $optionalCandidates = [
            'phone_number', 'telephone',
            'address', 'adresse', 'city', 'ville',
            'date_naissance', 'birth_date',
            'pseudo', 'username', 'display_name',
            'created_at', 'date_inscription', 'joined_at', 'created_on',
            'photo', 'profile_photo', 'avatar', 'photo_blob',
            'photo_path', 'photo_url', 'avatar_url', 'profile_image', 'image_path',
            'photo_mime_type', 'avatar_mime_type', 'mime_type', 'photo_type',
            'bio', 'about'
        ];

        $columns = $baseColumns;
        foreach ($optionalCandidates as $column) {
            if ($this->tableHasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        $sql = 'SELECT ' . implode(', ', $columns) . ' FROM users WHERE id = :id LIMIT 1';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("DB Error in getUserProfile: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Enregistre un nouvel utilisateur.
     * @param string $email
     * @param string $password Le mot de passe en clair.
     * @param string $firstName
     * @param string $lastName
     * @return bool Vrai en cas de succès, Faux sinon (email déjà utilisé ou erreur DB).
     */
    public function registerUser(string $email, string $password, string $firstName, string $lastName): bool {

        // 1. Vérification de l'existence de l'email
        if ($this->getUserByEmail($email)) {
            return false; // Email déjà utilisé
        }

        // 2. Hachage du mot de passe
        $hashedPassword = self::hashPassword($password); // Utilisation de la méthode statique

        $sql = "INSERT INTO users (email, password, first_name, last_name, credit_balance, is_driver, is_passenger, is_active)
                VALUES (:email, :password, :firstName, :lastName, :credit, :is_driver, :is_passenger, 1)";

        try {
            // Utilisation de $this->pdo
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                ':email' => $email,
                ':password' => $hashedPassword,
                ':firstName' => $firstName,
                ':lastName' => $lastName,
                ':credit' => 20,
                ':is_driver' => 0,
                ':is_passenger' => 1
            ]);

        } catch (PDOException $e) {
            error_log("DB Error during registration: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tente de connecter un utilisateur en vérifiant l'email et le mot de passe.
     * @param string $email
     * @param string $password
     * @return array|bool Les données de l'utilisateur (sans le hash) si la connexion réussit, sinon false.
     */
    public function login(string $email, string $password): array|bool {

        // Récupérer l'utilisateur (le hash du mot de passe est inclus ici)
        $user = $this->getUserByEmail($email);

        // 1. Vérifier si l'utilisateur existe ET si le mot de passe correspond au hash
        if ($user && password_verify($password, $user['password'])) {
                        if (array_key_exists('is_active', $user) && (int)$user['is_active'] !== 1) { return false; }
            // 2. Succès : retirer le hash pour la sécurité avant de retourner les données
            unset($user['password']);
            return $user;
        }

        return false; // Échec de la connexion
    }

    private function getTableColumns(string $table): array {
        $key = strtolower($table);
        if (!isset($this->columnCache[$key])) {
            try {
                $sql = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':table' => $table]);
                $this->columnCache[$key] = array_map('strtolower', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME'));
            } catch (PDOException $e) {
                error_log("DB Error while reading schema for {$table}: " . $e->getMessage());
                $this->columnCache[$key] = [];
            }
        }

        return $this->columnCache[$key];
    }

    private function tableHasColumn(string $table, string $column): bool {
        return in_array(strtolower($column), $this->getTableColumns($table), true);
    }

    /**
     * Soft-delete the user account: deactivate and anonymize PII.
     * Keeps referential integrity via existing foreign keys.
     */
    public function softDeleteUser(int $userId): bool {
        try {
            $this->pdo->beginTransaction();

            // Deactivate user
            $stmt = $this->pdo->prepare("UPDATE users
                SET is_active = 0,
                    email = CONCAT('deleted+', id, '@example.com'),
                    first_name = 'Compte',
                    last_name = 'supprimé',
                    password = '',
                    phone_number = NULL,
                    address = NULL,
                    pseudo = NULL,
                    photo = NULL,
                    photo_mime_type = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id");
            $stmt->execute([':id' => $userId]);

            // Optionally detach upcoming travels by setting available_seats to 0 (kept minimal: no-op)

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            try { $this->pdo->rollBack(); } catch (Throwable $ignored) {}
            error_log('softDeleteUser failed: ' . $e->getMessage());
            return false;
        }
    }
}
