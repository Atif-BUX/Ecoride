<?php
// Fichier: laisser_avis.php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/TravelManager.php';
require_once __DIR__ . '/src/ReviewManager.php';

if (file_exists(__DIR__ . '/src/Csrf.php')) {
    require_once __DIR__ . '/src/Csrf.php';
    Csrf::ensureToken();
}

$pdo = Database::getConnection();
if (!$pdo) {
    flash('profile_error', "Impossible d'enregistrer votre avis pour le moment.");
    header('Location: profil.php');
    exit;
}

$travelManager = new TravelManager($pdo);
$reviewManager = new ReviewManager($pdo);

$userId = currentUserId();
$travelId = filter_input(INPUT_GET, 'travel_id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $travelId = filter_input(INPUT_POST, 'travel_id', FILTER_VALIDATE_INT);
}

if (!$travelId || $travelId <= 0) {
    flash('profile_error', "Trajet introuvable.");
    header('Location: profil.php');
    exit;
}

$context = $travelManager->getReviewContext($travelId, $userId);
if (!$context) {
    flash('profile_error', "Vous ne pouvez pas laisser d'avis pour ce trajet.");
    header('Location: profil.php');
    exit;
}

$errors = [];
$rating = null;
$comment = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expirée. Veuillez réessayer.';
    } else {
        $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
        $comment = trim((string)($_POST['comment'] ?? ''));

        if ($rating === false || $rating < 1 || $rating > 5) {
            $errors[] = 'Veuillez sélectionner une note entre 1 et 5.';
        }

        if (empty($errors)) {
            $success = $reviewManager->leaveReview(
                $userId,
                (int)$context['driver_id'],
                $travelId,
                (int)$rating,
                $comment !== '' ? $comment : null
            );

            if ($success) {
                flash('profile_success', 'Merci ! Votre avis a bien été enregistré et sera publié après validation.');
                header('Location: profil.php');
                exit;
            }

            $errors[] = "Une erreur s'est produite lors de l'enregistrement de votre avis.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laisser un avis - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <?php $page_title='Laisser un avis — EcoRide'; $page_desc="Évaluez vos trajets et aidez la communauté EcoRide."; require __DIR__ . '/includes/layout/seo.php'; ?>
</head>
<body class="bg-light">
<?php require __DIR__ . '/includes/layout/navbar.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card search-tool-card p-4">
                <h1 class="h4 mb-3">Laisser un avis</h1>
                <p class="text-muted mb-4">
                    Trajet <strong><?= htmlspecialchars($context['departure_city']) ?> → <?= htmlspecialchars($context['arrival_city']) ?></strong>
                    du <?= htmlspecialchars(date('d/m/Y', strtotime($context['departure_date']))) ?>
                    à <?= htmlspecialchars(date('H:i', strtotime($context['departure_time']))) ?>.<br>
                    Conducteur : <?= htmlspecialchars($context['driver_first_name'] . ' ' . $context['driver_last_name']) ?>
                </p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="laisser_avis.php">
                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                    <input type="hidden" name="travel_id" value="<?= (int)$context['travel_id'] ?>">

                    <div class="mb-3">
                        <label for="rating" class="form-label fw-semibold">Note</label>
                        <select class="form-select" id="rating" name="rating" required>
                            <option value="">Choisir une note</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= ($rating === $i) ? 'selected' : '' ?>>
                                    <?= $i ?> <?= $i > 1 ? 'étoiles' : 'étoile' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="comment" class="form-label fw-semibold">Commentaire (optionnel)</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4"
                                  placeholder="Partagez votre expérience..."><?= htmlspecialchars($comment) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="profil.php" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-success">Envoyer l'avis</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>
