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
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/TravelManager.php';
// CSRF (si disponible) et logger NoSQL
if (file_exists(__DIR__ . '/src/Csrf.php')) { require_once __DIR__ . '/src/Csrf.php'; Csrf::ensureToken(); }
if (file_exists(__DIR__ . '/src/NoSqlLogger.php')) { require_once __DIR__ . '/src/NoSqlLogger.php'; }

// 4. Établir la connexion et récupérer les trajets
$pdo = Database::getConnection();

// LIGNE DE CORRECTION CRITIQUE
$travels = []; // Initialisation du tableau de trajets
$fallback_travels = []; // Résultats alternatifs (mêmes villes, autre date)
$db_error = null; // Initialisation de la variable d'erreur BDD
$need_success = null; // Message de succès pour "exprimer votre besoin"
$need_error = null;   // Message d'erreur pour "exprimer votre besoin"

// Traitement du formulaire "Exprimer votre besoin"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'express_need')) {
    if (class_exists('Csrf') && !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $need_error = "Jeton CSRF invalide. Veuillez réessayer.";
    } else {
        $need_payload = [
            'event'     => 'need',
            'user_id'   => (int)($_SESSION['user_id'] ?? 0),
            'departure' => trim($_POST['depart'] ?? ''),
            'arrival'   => trim($_POST['arrivee'] ?? ''),
            'date'      => trim($_POST['date_depart'] ?? ''),
            'note'      => trim($_POST['note'] ?? ''),
            'ts'        => date('c')
        ];
        try {
            if (class_exists('NoSqlLogger')) { NoSqlLogger::log('needs', $need_payload); }
            $need_success = "Votre demande a bien été enregistrée.";
        } catch (Throwable $e) {
            $need_error = "Impossible d’enregistrer votre demande pour le moment.";
        }
    }
}

// **********************************************
// LOGIQUE DE RECHERCHE
// **********************************************

// 1. Récupération et nettoyage des critères de recherche
$departure  = $_GET['depart'] ?? null;
$arrival    = $_GET['arrivee'] ?? null;
$date       = $_GET['date_depart'] ?? null;
$eco_only   = isset($_GET['eco_only']) && $_GET['eco_only'] === '1';
$max_price  = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$min_rating = isset($_GET['min_rating']) && $_GET['min_rating'] !== '' ? (float)$_GET['min_rating'] : null;
$near_days  = isset($_GET['near_days']) ? max(1, min(30, (int)$_GET['near_days'])) : 3; // Tolérance par défaut ±3 jours

// On détermine si une recherche a été effectuée
$is_searching = !empty($departure) || !empty($arrival) || !empty($date);

if ($pdo) {
    try {
        $travelManager = new TravelManager($pdo);

        if ($is_searching) {
            // 2. Si l'utilisateur cherche, appel avec filtres (US4)
            $travels = $travelManager->searchTravels($departure, $arrival, $date, $eco_only, $max_price, null, $min_rating);
            if (empty($travels) && (!empty($departure) || !empty($arrival)) && !empty($date)) {
                // Fallback: mêmes villes, dates proches (± near_days)
                $fallback_travels = $travelManager->searchTravelsNearDate($departure, $arrival, $date, $near_days);
            }
            if (empty($travels) && empty($date) && (!empty($departure) || !empty($arrival))) {
                $fallback_travels = $travelManager->searchTravelsNearDate($departure, $arrival, date('Y-m-d'), $near_days);
            }
        } else {
            // 3. Si aucun critère n'est soumis, ne rien afficher par défaut
            $travels = [];
        }
    } catch (Exception $e) {
        $db_error = "Erreur fatale lors de l'initialisation du Manager.";
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
    <?php $page_title='Covoiturages — EcoRide'; $page_desc="Recherchez des trajets disponibles et réservez facilement sur EcoRide."; require __DIR__ . '/includes/layout/seo.php'; ?>
</head>
<body class="bg-light">
<video autoplay muted loop playsinline id="bg-video">
    <source src="medias/ontheroad.mp4" type="video/mp4">
</video>


<?php require __DIR__ . '/includes/layout/navbar.php'; ?>


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
            <?php if (!empty($need_success)): ?>
                <div id="needAlert" class="alert alert-success text-center" role="alert" tabindex="-1">
                    <?= htmlspecialchars($need_success) ?>
                </div>
            <?php elseif (!empty($need_error)): ?>
                <div id="needAlert" class="alert alert-danger text-center" role="alert" tabindex="-1">
                    <?= htmlspecialchars($need_error) ?>
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
                    <input type="text" class="form-control" id="depart" name="depart" placeholder="Ville de départ" value="<?= htmlspecialchars($departure ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label for="arrivee" class="form-label fw-bold">Arrivée</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-location-arrow"></i></span>
                    <input type="text" class="form-control" id="arrivee" name="arrivee" placeholder="Ville d'arrivée" value="<?= htmlspecialchars($arrival ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label for="date_depart" class="form-label fw-bold">Date</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                    <input type="date" class="form-control" id="date_depart" name="date_depart" value="<?= htmlspecialchars($date ?? '') ?>">
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
                    <button type="submit" class="btn btn-lg fw-bold" style="background-color: var(--color-primary-dark); color: var(--color-neutral-white);">
                        Rechercher <i class="fas fa-search ms-2"></i>
                    </button>
                    <a href="covoiturages.php" class="btn btn-outline-secondary btn-lg fw-bold">
                        Réinitialiser <i class="fas fa-times-circle ms-2"></i>
                    </a>
                </div>
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
                    <?php
                        $photoSrc = null;
                        if (!empty($travel['user_photo_bin'])) {
                            $mime = !empty($travel['user_photo_mime']) ? $travel['user_photo_mime'] : 'image/jpeg';
                            $photoSrc = 'data:' . $mime . ';base64,' . base64_encode($travel['user_photo_bin']);
                        } elseif (!empty($travel['user_photo_path'])) {
                            $photoSrc = $travel['user_photo_path'];
                        }
                    ?>
                    <a href="detail_trajet.php?id=<?= htmlspecialchars($travel['id']) ?>"
                       class="card mb-4 shadow-sm search-tool-card text-decoration-none d-block p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <?php if ($photoSrc): ?>
                                    <img src="<?= htmlspecialchars($photoSrc) ?>" alt="Photo conducteur" class="rounded-circle me-3"
                                         style="width: 50px; height: 50px; object-fit: cover; border: 2px solid var(--color-primary-light);">
                                <?php else: ?>
                                    <i class="fas fa-user fa-2x rounded-circle me-3 p-2 d-flex justify-content-center align-items-center"
                                       style="width: 50px; height: 50px; color: var(--color-primary-dark); background-color: var(--color-primary-light);"></i>
                                <?php endif; ?>
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


                    <p class="mb-2"><strong>Vous êtes passager ?</strong></p>
                    <div class="card card-body text-start">
                        <form method="post" class="row g-2">
                            <input type="hidden" name="action" value="express_need">
                            <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                            <div class="col-12 col-md-4">
                                <label for="need_depart" class="form-label">Départ</label>
                                <input type="text" id="need_depart" name="depart" class="form-control" value="<?= htmlspecialchars($departure ?? '') ?>" placeholder="Ville de départ" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="need_arrivee" class="form-label">Arrivée</label>
                                <input type="text" id="need_arrivee" name="arrivee" class="form-control" value="<?= htmlspecialchars($arrival ?? '') ?>" placeholder="Ville d’arrivée" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="need_date" class="form-label">Date souhaitée</label>
                                <input type="date" id="need_date" name="date_depart" class="form-control" value="<?= htmlspecialchars($date ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label for="need_note" class="form-label">Message (optionnel)</label>
                                <textarea id="need_note" name="note" class="form-control" rows="2" placeholder="Précisez vos contraintes ou préférences"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">Enregistrer ma demande</button>
                            </div>
                        </form>
                    </div>


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
<script>
document.addEventListener('DOMContentLoaded', function() {
    var a = document.getElementById('needAlert');
    if (a) {
        try { a.scrollIntoView({behavior: 'smooth', block: 'start'}); } catch (e) {}
        if (a.focus) { a.focus(); }
    }
});
</script>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>





