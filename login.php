<?php
// Fichier: login.php (Code Final Propre)

// Active la mise en mémoire tampon de sortie (Garantit que le header() fonctionne)
ob_start();

// Le bloc d'inclusion qui a résolu le problème de chemin
$userFilePath = 'src/User.php';
if (!file_exists($userFilePath)) {
    echo "ERREUR FATALE : Le fichier User.php est introuvable à l'adresse relative : " . $userFilePath;
    exit;
}
require_once $userFilePath;

// Démarrer la session
session_start();

// Vérifier que le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email && $password) {

        $userManager = new User();
        $loggedInUser = $userManager->login($email, $password);

        if ($loggedInUser) {
            // Connexion réussie : Stocker les informations de l'utilisateur dans la session
            $_SESSION['user_id'] = $loggedInUser['id'];
            $_SESSION['user_firstname'] = $loggedInUser['first_name'];
            $_SESSION['is_logged_in'] = true;

            // Rediriger
            header("Location: index.php");
            exit();
        } else {
            // Échec de la connexion
            $_SESSION['login_error'] = "Email ou mot de passe incorrect.";
            header("Location: connexion.php");
            exit();
        }
    } else {
        // Données manquantes
        $_SESSION['login_error'] = "Veuillez fournir un email et un mot de passe valides.";
        header("Location: connexion.php");
        exit();
    }
} else {
    // Accès direct au script
    header("Location: connexion.php");
    exit();
}