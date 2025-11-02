<?php
// Fichier: src/UserManager.php

// Assurez-vous que le chemin vers Database.php est correct
require_once 'Database.php';

class UserManager {

    private PDO $pdo; // Propriété pour stocker l'objet PDO

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
        $sql = "SELECT id, email, password, first_name, last_name FROM users WHERE email = :email";

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

        $sql = "INSERT INTO users (email, password, first_name, last_name) VALUES (:email, :password, :firstName, :lastName)";

        try {
            // Utilisation de $this->pdo
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                ':email' => $email,
                ':password' => $hashedPassword,
                ':firstName' => $firstName,
                ':lastName' => $lastName
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
            // 2. Succès : retirer le hash pour la sécurité avant de retourner les données
            unset($user['password']);
            return $user;
        }

        return false; // Échec de la connexion
    }
}