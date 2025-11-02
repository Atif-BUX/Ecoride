<?php
// Fichier: login_process.php

// Démarrer la session
session_start();
// CSRF check
if (file_exists(__DIR__ . '/src/Csrf.php')) { require_once __DIR__ . '/src/Csrf.php'; }
if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
    $_SESSION['login_error'] = 'Session expirée. Veuillez réessayer.';
    header('Location: connexion.php');
    exit;
}

// Rediriger vers la page de connexion par défaut en cas d'accès direct sans POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: connexion.php');
    exit;
}

// 1. Inclure les classes nécessaires
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/UserManager.php';

// 2. Nettoyer les données (s'assurer qu'elles existent)
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 3. Validation de base
if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = "Veuillez saisir votre email et votre mot de passe.";
    header('Location: connexion.php');
    exit;
}

// 4. Tenter la connexion
$pdo = Database::getConnection();

if (!$pdo) {
    // Si la connexion BDD échoue, rediriger avec une erreur générique
    $_SESSION['login_error'] = "Erreur interne du serveur. Veuillez réessayer plus tard.";
    header('Location: connexion.php');
    exit;
}

try {
    $userManager = new UserManager($pdo);

    // Appel de la méthode login
    $user_data = $userManager->login($email, $password);

    if ($user_data) {
        // CONNEXION RÉUSSIE

        // Sécuriser la session (bonne pratique)
        session_regenerate_id(true);

        // Stocker les informations essentielles dans la session
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['user_email'] = $user_data['email'];
        $_SESSION['user_firstname'] = $user_data['first_name'];
        $_SESSION['is_logged_in'] = true;
        // Rafraîchir le token CSRF après connexion
        if (class_exists('Csrf')) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

        // Rediriger vers la page de covoiturages ou l'accueil
        header('Location: covoiturages.php');
        exit;

    } else {
        // ÉCHEC DE LA CONNEXION (mauvais identifiants)
        $_SESSION['login_error'] = "Identifiants incorrects. Veuillez vérifier votre email et votre mot de passe.";
        header('Location: connexion.php');
        exit;
    }

} catch (Exception $e) {
    // Erreur lors de l'exécution du Manager
    $_SESSION['login_error'] = "Une erreur est survenue lors du traitement. Veuillez réessayer.";
    error_log("Login error: " . $e->getMessage());
    header('Location: connexion.php');
    exit;
}

