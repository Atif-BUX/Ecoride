<?php
// Fichier: inscription.php

// 1. Démarrer la session
session_start();
// CSRF helper
if (file_exists(__DIR__ . '/src/Csrf.php')) { require_once __DIR__ . '/src/Csrf.php'; Csrf::ensureToken(); }

// Si l'utilisateur est déjà connecté, le rediriger vers l'accueil ou la liste des trajets
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header('Location: covoiturages.php');
    exit;
}

// 2. Inclure les classes nécessaires
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/UserManager.php';
require_once __DIR__ . '/src/RoleManager.php';

// Initialisation des variables
$error_message = null;
$success_message = null;

// 3. LOGIQUE DE TRAITEMENT DU FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $error_message = 'Session expirée. Veuillez réessayer.';
    } else {

    // a. Nettoyage des données du formulaire
    $firstName = trim(filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $lastName = trim(filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // b. Validation côté serveur
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($passwordConfirm)) {
        $error_message = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "L'adresse email est invalide.";
    } elseif ($password !== $passwordConfirm) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error_message = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        // c. Tentative de connexion à la BDD et inscription
        $pdo = Database::getConnection();

        if ($pdo) {
            try {
                // Instanciation du Manager avec injection de dépendance
                $userManager = new UserManager($pdo);

                $wasCreated = $userManager->registerUser($email, $password, $firstName, $lastName);
                if ($wasCreated) {
                    $newUserId = (int)$pdo->lastInsertId();
                    if ($newUserId > 0) {
                        $roleManager = new RoleManager($pdo);
                        $roleManager->assignRole($newUserId, 'USER');
                    }
                    // Succès de l'inscription
                    $success_message = "Félicitations ! Votre compte a été créé. Vous pouvez maintenant vous connecter.";
                    // Optionnel: Nettoyer les champs après succès
                    unset($firstName, $lastName, $email);

                } else {
                    $error_message = "L'inscription a échoué. Cet email est peut-être déjà utilisé.";
                }

            } catch (Exception $e) {
                // Erreur fatale (manager non initialisé, etc.)
                $error_message = "Une erreur interne est survenue. Veuillez réessayer.";
                // En production, on loggerait $e->getMessage();
            }
        } else {
            $error_message = "Erreur de connexion à la base de données. Veuillez contacter l'administrateur.";
        }
    }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --color-primary-dark: #1e8449;
            --color-primary-light: #32CD32;
            --color-neutral-white: #ffffff;
        }
    </style>
    <?php $page_title='Inscription — EcoRide'; $page_desc="Créez votre compte EcoRide et commencez à covoiturer dès aujourd\'hui."; require __DIR__ . '/includes/layout/seo.php'; ?>
</head>
<body class="bg-light">
<video autoplay muted loop playsinline id="bg-video">
    <source src="medias/ontheroad.mp4" type="video/mp4">
</video>

<?php require __DIR__ . '/includes/layout/navbar.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card p-4 shadow-lg text-dark">
                <h1 class="text-center mb-4" style="color: var(--color-primary-dark);">Créer un compte</h1>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success text-center" role="alert">
                        <?= htmlspecialchars($success_message) ?>
                        <p class="mt-2 mb-0"><a href="connexion.php" class="alert-link">Se connecter</a></p>
                    </div>
                <?php endif; ?>

<form method="POST" action="inscription.php" class="row g-3">
    <?= class_exists('Csrf') ? Csrf::input() : '' ?>

                    <div class="col-md-6">
                        <label for="prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" required
                               value="<?= htmlspecialchars($firstName ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" required
                               value="<?= htmlspecialchars($lastName ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?= htmlspecialchars($email ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label for="password" class="form-label">Mot de passe (8 caractères min.)</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="col-12">
                        <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-lg w-100 fw-bold text-white"
                                style="background-color: var(--color-primary-dark);">
                            S'inscrire
                        </button>
                    </div>

                    <div class="col-12 text-center mt-3">
                        <p class="mb-0">Déjà un compte ? <a href="connexion.php" style="color: var(--color-primary-dark);">Connectez-vous ici</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>




