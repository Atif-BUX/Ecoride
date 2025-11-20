<?php
// Fichier: deconnexion.php

// 1. Démarrer la session (nécessaire pour pouvoir la détruire)
session_start();

// 2. Détruire toutes les variables de session
$_SESSION = array();

// 3. Détruire le cookie de session (pour s'assurer que le navigateur l'oublie)
// C'est la méthode recommandée et sécurisée pour la déconnexion
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Détruire la session côté serveur
session_destroy();

// 5. Rediriger l'utilisateur vers la page de connexion
header("Location: connexion.php");
exit();