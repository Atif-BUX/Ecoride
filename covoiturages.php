<?php
// Fichier: covoiturages.php - 02/11/2025 - 19:20


// 1. Démarrer la session
session_start();


// 2. Définir les variables de navigation
$is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
$firstname = $_SESSION['user_firstname'] ?? 'Utilisateur';


// **********************************************
// LOGIQUE DE RÉCUPÉRATION DES TRAJETS
// **********************************************


// 3. Inclure les classes de BDD
// Ces chemins DOIVENT être corrects par rapport à la racine du projet
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/TravelManager.php';


// 4. Établir la connexion et récupérer les trajets
$pdo = Database::getConnection();


// LIGNE DE CORRECTION CRITIQUE
$travels = []; // Initialisation du tableau de trajets
$fallback_travels = []; // Résultats alternatifs (mêmes villes, autre date)
$db_error = null; // Initialisation de la variable d'erreur BDD


// **********************************************
// LOGIQUE DE RECHERCHE
// **********************************************


// 1. Récupération et nettoyage des critères de recherche
$departure = $_GET['depart'] ?? null;
$arrival = $_GET['arrivee'] ?? null;
$date = $_GET['date_depart'] ?? null;
$near_days = isset($_GET['near_days']) ? max(1, min(30, (int)$_GET['near_days'])) : 3; // Tolérance par défaut ±3 jours


// On détermine si une recherche a été effectuée
$is_searching = !empty($departure) || !empty($arrival) || !empty($date);


if ($pdo) {
    try {
        $travelManager = new TravelManager($pdo);


        if ($is_searching) {
            // 2. Si l'utilisateur cherche, appeler une nouvelle méthode de recherche
            $travels = $travelManager->searchTravels($departure, $arrival, $date);
            if (empty($travels) && (!empty($departure) || !empty($arrival)) && !empty($date)) {
                // Fallback: mêmes villes, dates proches (± near_days)
                $fallback_travels = $travelManager->searchTravelsNearDate($departure, $arrival, $date, $near_days);
            }
            if (empty($travels) && empty($date) && (!empty($departure) || !empty($arrival))) {
                $fallback_travels = $travelManager->searchTravelsNearDate($departure, $arrival, date('Y-m-d'), $near_days);
            }
        } else {
            // 3. Si aucun critère n'est soumis, afficher tous les trajets (comportement par défaut)
            $travels = $travelManager->getAllTravels();
        }


    } catch (Exception $e) {
        $db_error = "Erreur fatale lors de l'initialisation du Manager.";
        // En production, on loggerait $e->getMessage();
    }
} else {
    $db_error = "ERREUR FATALE: La connexion à la base de données a échoué. Vérifiez Database.php.";
}


// Gérer le message de succès venant de proposer_trajet.php
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']); // Nettoyer la session après affichage


?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Covoiturages - EcoRide</title>
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
                            <a class="nav-link" href="profil.php">Profil</a>
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
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if ($db_error): ?>
                <div class="alert alert-danger text-center" role="alert">
                    **ERREUR DE CONNEXION/INITIALISATION :** <?php echo htmlspecialchars($db_error); ?>
                </div>
            <?php endif; ?>


            <?php if ($success_message): ?>
                <div class="alert alert-success text-center" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <h1 class="text-center mb-4 text-white">Tous les Covoiturages Disponibles</h1>


    <div class="search-section bg-white p-4 rounded shadow-lg mb-5">
        <h2 class="text-center text-dark mb-4">Affiner votre recherche</h2>


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
                <label for="near_days" class="form-label fw-bold">Tolerance date (± jours)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-arrows-alt-h"></i></span>
                    <input type="number" min="1" max="30" class="form-control" id="near_days" name="near_days" value="<?= (int)$near_days ?>">
                </div>
            </div>

            <div class="col-12 text-center mt-4">
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <button type="submit" class="btn btn-lg fw-bold"
                            style="background-color: var(--color-primary-dark); color: var(--color-neutral-white);">
                        Rechercher <i class="fas fa-search ms-2"></i>
                    </button>
                    <a href="covoiturages.php" class="btn btn-outline-secondary btn-lg fw-bold">
                        Réinitialiser <i class="fas fa-times-circle ms-2"></i>
                    </a>
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

            <div class="col-md-4">
                <label for="near_days" class="form-label fw-bold">Tolérance date (± jours)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-arrows-alt-h"></i></span>
                    <input type="number" min="1" max="30" class="form-control" id="near_days" name="near_days" value="<?= (int)$near_days ?>">
                </div>
            </div>


            <div class="col-12 text-center mt-4">
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <button type="submit" class="btn btn-lg fw-bold"
                            style="background-color: var(--color-primary-dark); color: var(--color-neutral-white);">
                        Rechercher <i class="fas fa-search ms-2"></i>
                    </button>
                    <a href="covoiturages.php" class="btn btn-outline-secondary btn-lg fw-bold">
                        Réinitialiser <i class="fas fa-times-circle ms-2"></i>
                    </a>
                </div>

                <?php if (!empty($fallback_travels)): ?>
                    <div class="alert alert-secondary text-center py-3 my-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php if (!empty($date)): ?>
                            Aucun trajet le <?= htmlspecialchars(date('d/m/Y', strtotime($date))) ?>.
                        <?php else: ?>
                            Aucun trajet strict pour les villes indiquées.
                        <?php endif; ?>
                        Suggestions dans une fenêtre de ± <?= (int)$near_days ?> jours<?= (!empty($departure) || !empty($arrival)) ? ' pour ' . htmlspecialchars($departure ?? '') . (!empty($departure) && !empty($arrival) ? ' → ' : '') . htmlspecialchars($arrival ?? '') : '' ?>.
                    </div>
                    <?php foreach ($fallback_travels as $travel): ?>
                        <a href="detail_trajet.php?id=<?= htmlspecialchars($travel['id']) ?>"
                           class="card mb-3 shadow-sm search-tool-card text-decoration-none d-block p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-0 fw-bold" style="color: var(--color-primary-dark);">
                                        <?= htmlspecialchars($travel['departure_city']) ?>
                                        <i class="fas fa-long-arrow-alt-right mx-2"></i>
                                        <?= htmlspecialchars($travel['arrival_city']) ?>
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        Départ le <?= htmlspecialchars(date('d/m/Y', strtotime($travel['departure_date']))) ?>
                                        à <?= htmlspecialchars(date('H:i', strtotime($travel['departure_time']))) ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <span class="badge rounded-pill fs-6 p-2"
                                          style="background-color: var(--color-primary-light); color: var(--color-neutral-white);">
                                      <?= htmlspecialchars(number_format($travel['price_per_seat'], 2, ',', ' ')) ?> €
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($is_searching): ?>
            <div class="mt-3">
                <span class="badge bg-success me-2">Filtres actifs</span>
                <?php
                $qs = $_GET;
                $chips = [];
                if (!empty($departure)) { $tmp = $qs; unset($tmp['depart']); $chips[] = ['Départ: ' . $departure, 'covoiturages.php' . (empty($tmp)?'':'?' . http_build_query($tmp))]; }
                if (!empty($arrival)) { $tmp = $qs; unset($tmp['arrivee']); $chips[] = ['Arrivée: ' . $arrival, 'covoiturages.php' . (empty($tmp)?'':'?' . http_build_query($tmp))]; }
                if (!empty($date)) { $tmp = $qs; unset($tmp['date_depart']); $chips[] = ['Date: ' . $date, 'covoiturages.php' . (empty($tmp)?'':'?' . http_build_query($tmp))]; }
                if (!empty($near_days)) { $tmp = $qs; unset($tmp['near_days']); $chips[] = ['± ' . (int)$near_days . 'j', 'covoiturages.php' . (empty($tmp)?'':'?' . http_build_query($tmp))]; }
                foreach ($chips as $chip): ?>
                    <a href="<?= htmlspecialchars($chip[1]) ?>" class="badge bg-secondary text-decoration-none me-1">
                        <?= htmlspecialchars($chip[0]) ?> <i class="fas fa-times ms-1"></i>
                    </a>
                <?php endforeach; ?>
                <a href="covoiturages.php" class="badge bg-light text-dark border text-decoration-none ms-2">Tout effacer</a>
            </div>
        <?php endif; ?>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-8">


            <?php if (!empty($travels)): ?>
                <?php foreach ($travels as $travel): ?>
                    <a href="detail_trajet.php?id=<?= htmlspecialchars($travel['id']) ?>"
                       class="card mb-4 shadow-sm search-tool-card text-decoration-none d-block p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user fa-2x rounded-circle me-3 p-2 d-flex justify-content-center align-items-center"
                                   style="width: 50px; height: 50px; color: var(--color-primary-dark); background-color: var(--color-primary-light);"></i>
                                <div>
                                    <p class="mb-0 fw-bold" style="color: var(--color-primary-dark);">
                                        <?= htmlspecialchars($travel['first_name'] . ' ' . $travel['last_name']) ?>
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        <?= htmlspecialchars(date('H:i', strtotime($travel['departure_time']))) ?>
                                        – **<?= htmlspecialchars($travel['departure_city']) ?>**
                                        <i class="fas fa-long-arrow-alt-right mx-2"></i>
                                        **<?= htmlspecialchars($travel['arrival_city']) ?>**
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        Départ le <?= htmlspecialchars(date('d/m/Y', strtotime($travel['departure_date']))) ?>
                                    </p>
                                    <p class="mb-0 text-muted small fst-italic">
                                        <?= htmlspecialchars($travel['description']) ?>
                                    </p>
                                    <?php if (!empty($travel['car_details'])): ?>
                                        <p class="mb-0 text-muted small">
                                            <i class="fas fa-car-side me-1"></i> Véhicule: <?= htmlspecialchars($travel['car_details']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>


                            <div class="text-end">
                               <span class="badge rounded-pill fs-5 p-2"
                                     style="background-color: var(--color-primary-light); color: var(--color-neutral-white);">
                                   <?= htmlspecialchars(number_format($travel['price_per_seat'], 2, ',', ' ')) ?> €
                               </span>
                                <p class="text-muted small mb-0">
                                    <?= htmlspecialchars($travel['available_seats']) ?> place(s)
                                </p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center py-4 my-3" role="alert">
                    <h4 class="alert-heading">Aucun trajet trouvé 😔</h4>
                    <p>
                        Désolé, nous n'avons trouvé aucun trajet correspondant à votre recherche.
                    </p>
                    <hr>


                    <p class="mb-2">
                        **Vous êtes passager ?** <a href="#" onclick="alert('Cette fonctionnalité est en cours de développement. Bientôt, vous pourrez être notifié lorsqu\'un trajet correspondant sera disponible !')" class="alert-link fw-bold" style="color: var(--color-primary-dark);">Cliquez ici pour exprimer votre besoin</a>.
                    </p>


                    <p class="mb-0">
                        **Vous êtes conducteur ?**
                        <?php if ($is_logged_in): ?>
                            <a href="proposer_trajet.php" class="alert-link fw-bold" style="color: var(--color-primary-dark);">Proposez votre trajet maintenant</a> pour aider d'autres membres !
                        <?php else: ?>
                            <a href="connexion.php" class="alert-link fw-bold" style="color: var(--color-primary-dark);">Connectez-vous</a> pour proposer un trajet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>


        </div>
    </div>

</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>



