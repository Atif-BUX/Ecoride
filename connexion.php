<?php
// Fichier: connexion.php

// Déplacez tout le PHP en haut !
session_start();

// Charger la classe CSRF pour générer un token valide dans le formulaire
if (file_exists(__DIR__ . '/src/Csrf.php')) {
    require_once __DIR__ . '/src/Csrf.php';
    Csrf::ensureToken();
}

// Si l'utilisateur est déjà connecté, le rediriger vers la liste des trajets
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header('Location: covoiturages.php');
    exit;
}

// Récupérer et effacer les messages de session
$login_error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
$reset_info = $_SESSION['reset_info'] ?? null;
unset($_SESSION['reset_info']);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <?php $page_title='Connexion — EcoRide'; $page_desc="Connectez-vous pour réserver ou proposer des trajets sur EcoRide."; require __DIR__ . '/includes/layout/seo.php'; ?>
    <style>
        :root {
            --color-primary-dark: #1e8449;
            --color-primary-light: #32CD32;
            --color-neutral-white: #ffffff;
        }
    </style>
</head>
<body class="bg-light">

<?php require __DIR__ . '/includes/layout/navbar.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">

            <h1 class="text-center mb-4" style="color: var(--color-primary-dark);">Accéder à mon compte</h1>
            <p class="text-center text-muted mb-4">
                Entrez vos identifiants pour vous connecter à EcoRide.
            </p>

            <?php if ($reset_info): ?>
                <div class="alert alert-info text-center" role="alert">
                    <?= htmlspecialchars($reset_info) ?>
                </div>
            <?php endif; ?>

            <?php if ($login_error): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <?= htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>

            <div class="p-4 shadow-sm search-tool-card">
                <form action="login_process.php" method="POST" id="connexionForm" novalidate>
                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold d-flex align-items-center" style="color: var(--color-primary-dark);">
                            <i class="fas fa-envelope me-2"></i> Email
                        </label>
                        <input type="email" class="form-control" id="email" placeholder="contact@exemple.fr" required name="email">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-bold d-flex align-items-center" style="color: var(--color-primary-dark);">
                            <i class="fas fa-lock me-2"></i> Mot de passe
                        </label>
                        <input type="password" class="form-control" id="password" placeholder="Votre mot de passe" required name="password">
                        <div class="form-text text-end">
                            <a href="mot_de_passe_oublie.php" class="small text-muted">Mot de passe oublié ?</a>
                        </div>
                    </div>

                    <button type="submit" class="w-100 btn btn-lg fw-bold text-white" style="background-color: var(--color-primary-dark);">
                        <i class="fas fa-sign-in-alt me-2"></i> Connexion
                    </button>

                    <p class="text-center mt-3">
                        Pas encore membre ? <a href="inscription.php" style="color: var(--color-primary-dark);">Créez un compte ici</a>.
                    </p>
            </form>

            <div class="alert alert-secondary mt-3" role="alert">
                <strong>Comptes démo</strong> :
                <ul class="mb-0">
                    <li>Conducteur : <code>jean.dupont@test.fr</code> / <code>password321</code></li>
                    <li>Passager : <code>john.wick@gmail.com</code> / <code>password321</code></li>
                </ul>
                <small class="text-muted">Astuce : si l’authentification échoue, un administrateur peut ouvrir <code>admin_params.php</code> et cliquer sur « Réinitialiser les mots de passe démo » pour (re)définir ces identifiants.</small>
            </div>
            </div>

        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>



