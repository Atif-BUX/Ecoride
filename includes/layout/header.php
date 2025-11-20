<?php
// Basic shared header. Assumes includes/bootstrap.php already required so session and helpers exist.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
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
                    <li class="nav-item"><a class="nav-link" href="covoiturages.php">Covoiturages</a></li>
                    <li class="nav-item"><a class="nav-link" href="proposer_trajet.php">Proposer</a></li>
                    <li class="nav-item"><a class="nav-link" href="profil.php">Profil</a></li>
                    <?php if (function_exists('userHasRole') && userHasRole('EMPLOYE')): ?>
                        <li class="nav-item"><a class="nav-link" href="employe_reviews.php">Modération</a></li>
                    <?php endif; ?>
                    <?php if (function_exists('userHasRole') && userHasRole('ADMIN')): ?>
                        <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Admin</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin_params.php">Paramètres</a></li>
                    <?php endif; ?>
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="deconnexion.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="connexion.php">Connexion</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
</header>
