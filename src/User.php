<?php
// Fichier: src/User.php

require_once __DIR__ . '/Database.php';

class User {

    /**
     * Tente de connecter un utilisateur en vérifiant l'email et le mot de passe.
     * @param string $email
     * @param string $password
     * @return array|bool Les données de l'utilisateur si la connexion réussit, sinon false.
     */
    public function login(string $email, string $password): array|bool {
        $db = Database::getConnection();

        // Requête préparée pour récupérer l'utilisateur par email
        $sql = "SELECT id, first_name, last_name, email, password FROM users WHERE email = :email";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Le mot de passe haché correspond. On retire le hash avant de retourner.
            unset($user['password']);
            return $user;
        }

        return false;
    }

    /**
     * Crée un hash sécurisé pour un mot de passe (à utiliser lors de l'inscription).
     * @param string $password
     * @return string Le hash du mot de passe.
     */
    public static function hashPassword(string $password): string {
        // Justification Technique : PASSWORD_ARGON2ID est l'algorithme recommandé,
        // mais PASSWORD_DEFAULT (Bcrypt) est aussi largement accepté et très sécurisé.
        return password_hash($password, PASSWORD_DEFAULT);
    }
}