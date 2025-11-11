<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Compte - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <?php $page_title='Créer un compte — EcoRide'; $page_desc="Inscrivez-vous à EcoRide pour profiter d\'un covoiturage simple et écologique."; require __DIR__ . '/includes/layout/seo.php'; ?>
</head>
<body class="bg-light">

<?php require __DIR__ . '/includes/layout/navbar.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">

            <h1 class="text-center mb-4" style="color: var(--color-primary-dark);">Créer mon compte</h1>
            <p class="text-center text-muted mb-4">
                Rejoignez la communauté EcoRide et recevez
                <span class="fw-bold" style="color: var(--color-primary-light);">20 crédits offerts</span> ! 🥳
            </p>

            <div class="p-4 shadow-sm search-tool-card">
                <form action="#" method="POST">

                    <div class="mb-3">
                        <label for="pseudo" class="form-label fw-bold d-flex align-items-center" style="color: var(--color-primary-dark);">
                            <i class="fas fa-user-circle me-2"></i> Pseudo
                        </label>
                        <input type="text" class="form-control" id="pseudo" placeholder="Votre pseudo unique" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold d-flex align-items-center" style="color: var(--color-primary-dark);">
                            <i class="fas fa-envelope me-2"></i> Email
                        </label>
                        <input type="email" class="form-control" id="email" placeholder="contact@exemple.fr" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold d-flex align-items-center" style="color: var(--color-primary-dark);">
                            <i class="fas fa-lock me-2"></i> Mot de passe
                        </label>
                        <input type="password" class="form-control" id="password" placeholder="Mot de passe sécurisé" required>
                        <div class="form-text">
                            Doit contenir au moins 8 caractères, une majuscule et un chiffre. (Sécurité)
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm-password" class="form-label fw-bold d-flex align-items-center" style="color: var(--color-primary-dark);">
                            <i class="fas fa-lock me-2"></i> Confirmer mot de passe
                        </label>
                        <input type="password" class="form-control" id="confirm-password" placeholder="Confirmer votre mot de passe" required>
                    </div>

                    <button type="submit" class="w-100 main-btn btn btn-lg">
                        <i class="fas fa-check-circle me-2"></i> Je m'inscris !
                    </button>

                    <p class="text-center mt-3">
                        Déjà un compte ? <a href="connexion.php" style="color: var(--color-primary-dark);">Connectez-vous ici</a>.
                    </p>
                </form>
            </div>

        </div>
    </div>

</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>

