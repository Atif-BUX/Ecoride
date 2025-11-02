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
</head>
<body class="bg-light">
<video autoplay muted loop playsinline id="bg-video">
    <source src="medias/ontheroad.mp4" type="video/mp4">
</video>
<header class="main-header text-white py-3 sticky-top">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <svg id="logo-animation" width="40" height="40" viewBox="0 0 100 100" class="me-2">
                    <circle class="wheel-circle" cx="50" cy="50" r="45" stroke="#32CD32" stroke-width="5" fill="none" />
                    <g class="wheel-spokes" stroke="#32CD32" stroke-width="4" stroke-linecap="round">
                        <line x1="50" y1="5" x2="50" y2="95" />
                        <line x1="15" y1="27" x2="85" y2="73" />
                        <line x1="85" y1="27" x2="15" y2="73" />
                    </g>
                    <text class="logo-text" x="50" y="60" text-anchor="middle" font-family="Montserrat, sans-serif" font-size="45" font-weight="bold" fill="#FFFFFF">ER</text>
                </svg>
                <span class="text-white fw-bold">EcoRide</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="covoiturages.php">Covoiturages</a>
                    </li>

                    <?php if ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="proposer_trajet.php">Proposer</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="deconnexion.php">Déconnexion</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="connexion.php">Connexion</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="container py-5">
    <h1 class="text-center mb-4">Bienvenue, <?php echo htmlspecialchars($firstname); ?> !</h1>
    <p class="text-center lead">Trouvez ou proposez votre prochain covoiturage.</p>

    <div class="text-center mb-5">
        <h1 class="display-4 fw-bolder mb-3" style="color: var(--color-primary-dark);" id="slogan-animation">
            Voyagez Vert, Voyagez Ensemble.
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
        <h2 class="text-center mb-4" style="color: var(--color-primary-dark);">Pourquoi EcoRide ?</h2>
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
        <h2 class="text-center mb-4" style="color: var(--color-primary-dark);">La Communauté EcoRide</h2>
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <img src="graphics/heureux-amis.jpg" class="img-fluid rounded shadow" alt="Covoiturage en ville">
            </div>
            <div class="col-12 col-md-4">
                <img src="graphics/couple-voyage.jpg" class="img-fluid rounded shadow" alt="Voyage longue distance">
            </div>
            <div class="col-12 col-md-4">
                <img src="graphics/amis-ensemble.jpg" class="img-fluid rounded shadow" alt="Aide au chargement des bagages">
            </div>
        </div>
    </section>


</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>

