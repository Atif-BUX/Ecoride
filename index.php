<?php
// Fichier: index.php

// 1. Démarrer la session
session_start();

// 2. Vérifier si l'utilisateur est connecté, SANS BLOQUER L'ACCÈS
$is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;

// 3. Récupérer le prénom de l'utilisateur pour l'affichage personnalisé
// Le prénom n'est récupéré que si l'utilisateur est connecté.
$firstname = $is_logged_in ? ($_SESSION['user_firstname'] ?? 'Utilisateur') : 'Visiteur';

// Le reste de votre code HTML/PHP suit...
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Plateforme de Covoiturage Écologique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <?php $page_title = 'EcoRide - Plateforme de Covoiturage Écologique'; $page_desc = "Trouvez ou proposez un covoiturage simplement avec EcoRide, la plateforme écoresponsable."; require __DIR__ . '/includes/layout/seo.php'; ?>
    <style>
        .hero-heading,
        .hero-slogan,
        .section-heading {
            color: #2c2c2c;
        }
        .highlight-green {
            color: var(--color-primary-dark);
        }
    </style>
</head>
<body class="bg-light">
<video autoplay muted loop playsinline id="bg-video">
    <source src="medias/ontheroad.mp4" type="video/mp4">
</video>
<?php require __DIR__ . '/includes/layout/navbar.php'; ?>

<main class="container py-5">
    <h1 class="text-center mb-4 hero-heading">Bienvenue, <span class="highlight-green"><?php echo htmlspecialchars($firstname); ?></span> !</h1>
    <p class="text-center lead">Trouvez ou proposez votre prochain covoiturage.</p>

    <div class="text-center mb-5">
        <h1 class="display-4 fw-bolder mb-3 hero-slogan" id="slogan-animation">
            Voyagez <span class="highlight-green">Vert</span>, Voyagez Ensemble.
        </h1>
        <p class="lead text-muted">
            Votre solution de covoiturage simple, économique et écologique.
        </p>
    </div>

    <div class="search-section bg-white p-4 rounded shadow-lg mb-5">
        <h2 class="text-center text-dark mb-4">Trouvez votre prochain EcoRide</h2>

        <form action="covoiturages.php" method="GET" class="row g-3">

            <div class="col-md-4">
                <label for="depart" class="form-label fw-bold">Départ</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                    <input type="text"
                           class="form-control"
                           id="depart"
                           name="depart"
                           placeholder="Ville de départ"
                           value="<?= htmlspecialchars($departure ?? '') ?>">
                </div>
            </div>

            <div class="col-md-4">
                <label for="arrivee" class="form-label fw-bold">Arrivée</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-location-arrow"></i></span>
                    <input type="text"
                           class="form-control"
                           id="arrivee"
                           name="arrivee"
                           placeholder="Ville d'arrivée"
                           value="<?= htmlspecialchars($arrival ?? '') ?>">
                </div>
            </div>

            <div class="col-md-4">
                <label for="date_depart" class="form-label fw-bold">Date</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                    <input type="date"
                           class="form-control"
                           id="date_depart"
                           name="date_depart"
                           value="<?= htmlspecialchars($date ?? '') ?>">
                </div>
            </div>

            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-lg w-50 fw-bold"
                        style="background-color: var(--color-primary-dark); color: var(--color-neutral-white);">
                    Rechercher un trajet <i class="fas fa-car ms-2"></i>
                </button>
            </div>
        </form>
    </div>

    <section class="mb-5">
        <h2 class="text-center mb-4 section-heading">Pourquoi EcoRide ?</h2>
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <i class="fas fa-leaf display-4 mb-3" style="color: var(--color-primary-light);"></i>
                <h3 class="fs-5 fw-bold">Écologique</h3>
                <p class="text-muted">Réduisez vos émissions de CO2 en partageant vos trajets.</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="fas fa-euro-sign display-4 mb-3" style="color: var(--color-primary-light);"></i>
                <h3 class="fs-5 fw-bold">Économique</h3>
                <p class="text-muted">Divisez les coûts d'essence et de péages.</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="fas fa-smile display-4 mb-3" style="color: var(--color-primary-light);"></i>
                <h3 class="fs-5 fw-bold">Social</h3>
                <p class="text-muted">Faites de nouvelles rencontres et voyagez en bonne compagnie.</p>
            </div>
        </div>
    </section>

    <section class="image-gallery py-4">
        <h2 class="text-center mb-4 section-heading">La Communauté EcoRide</h2>
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <img src="graphics/heureux-amis.webp" class="img-fluid rounded shadow" alt="Covoiturage en ville">
            </div>
            <div class="col-12 col-md-4">
                <img src="graphics/couple-voyage.webp" class="img-fluid rounded shadow" alt="Voyage longue distance">
            </div>
            <div class="col-12 col-md-4">
                <img src="graphics/amis-ensemble.webp" class="img-fluid rounded shadow" alt="Aide au chargement des bagages">
            </div>
        </div>
    </section>


</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>

