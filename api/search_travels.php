<?php
// API: /api/search_travels.php
// Retourne la liste des trajets au format JSON (utilisé par la recherche AJAX)

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/TravelManager.php';

try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Connexion impossible à la base de données']);
    exit;
}

$travelManager = new TravelManager($pdo);

$departure  = trim($_GET['depart'] ?? '');
$arrival    = trim($_GET['arrivee'] ?? '');
$date       = trim($_GET['date_depart'] ?? '');
$ecoOnly    = isset($_GET['eco_only']) && in_array($_GET['eco_only'], ['1', 'true'], true);
$maxPrice   = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;
$minRating  = isset($_GET['min_rating']) && $_GET['min_rating'] !== '' ? (float) $_GET['min_rating'] : null;
$nearDays   = isset($_GET['near_days']) ? max(1, min(30, (int) $_GET['near_days'])) : 3;

$isSearching = $departure !== '' || $arrival !== '' || $date !== '';

$data = [];
$fallback = [];
$message = '';

try {
    if ($isSearching) {
        $rows = $travelManager->searchTravels(
            $departure !== '' ? $departure : null,
            $arrival !== '' ? $arrival : null,
            $date !== '' ? $date : null,
            $ecoOnly,
            $maxPrice,
            null,
            $minRating
        );
        $data = array_map('format_travel_payload', $rows);

        if (empty($data) && $departure !== '' && $arrival !== '' && $date !== '') {
            $fallbackRows = $travelManager->searchTravelsNearDate($departure, $arrival, $date, $nearDays);
            $fallback = array_map('format_travel_payload', $fallbackRows);
            $message = 'Aucune correspondance exacte, voici des dates proches.';
        }
    } else {
        $message = 'Indiquez au moins un critère pour lancer la recherche.';
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la recherche']);
    exit;
}

echo json_encode([
    'success'   => true,
    'travels'   => $data,
    'fallback'  => $fallback,
    'message'   => $message,
]);

function format_travel_payload(array $travel): array
{
    $driver = trim(($travel['first_name'] ?? '') . ' ' . ($travel['last_name'] ?? ''));
    $photoSrc = null;

    if (!empty($travel['user_photo_bin'])) {
        $mime = !empty($travel['user_photo_mime']) ? $travel['user_photo_mime'] : 'image/jpeg';
        $photoSrc = 'data:' . $mime . ';base64,' . base64_encode($travel['user_photo_bin']);
    } elseif (!empty($travel['user_photo_path'])) {
        $photoSrc = $travel['user_photo_path'];
    }

    $departureDate = $travel['departure_date'] ?? '';
    $departureTime = $travel['departure_time'] ?? '';

    return [
        'id'                => (int) ($travel['id'] ?? 0),
        'driver'            => $driver !== '' ? $driver : 'Conducteur',
        'departure_city'    => $travel['departure_city'] ?? '',
        'arrival_city'      => $travel['arrival_city'] ?? '',
        'departure_date'    => $departureDate,
        'departure_time'    => $departureTime,
        'date_label'        => $departureDate !== '' ? date('d/m/Y', strtotime($departureDate)) : '',
        'time_label'        => $departureTime !== '' ? date('H:i', strtotime($departureTime)) : '',
        'available_seats'   => (int) ($travel['available_seats'] ?? 0),
        'price_per_seat'    => isset($travel['price_per_seat']) ? (float) $travel['price_per_seat'] : 0.0,
        'description'       => $travel['description'] ?? '',
        'car_details'       => $travel['car_details'] ?? '',
        'photo'             => $photoSrc,
        'detail_url'        => 'detail_trajet.php?id=' . (int) ($travel['id'] ?? 0),
    ];
}
