<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (file_exists(__DIR__ . '/src/Csrf.php')) { require_once __DIR__ . '/src/Csrf.php'; Csrf::ensureToken(); }
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/PasswordResetManager.php';
require_once __DIR__ . '/src/SystemParameterManager.php';

$pdo = Database::getConnection();
$info = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!class_exists('Csrf') || !Csrf::validateRequest($_POST['csrf_token'] ?? '')) {
        $error = 'Jeton CSRF invalide.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Veuillez saisir un email valide.";
        } elseif (!$pdo) {
            $error = "Erreur de connexion à la base de données.";
        } else {
            try {
                $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = :e LIMIT 1');
                $stmt->execute([':e' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                // Toujours répondre de manière générique
                $info = "Si un compte correspond, un lien de réinitialisation a été envoyé.";
                if ($user) {
                    $pr = new PasswordResetManager($pdo);
                    // TTL configurable via paramètres système (fallback 60)
                    $ttl = 60;
                    try {
                        $spm = new SystemParameterManager($pdo);
                        $params = $spm->listAll('default');
                        if (isset($params['password_reset_ttl_minutes'])) {
                            $ttl = max(5, min(240, (int)$params['password_reset_ttl_minutes']));
                        }
                    } catch (Throwable $e) {}
                    $data = $pr->createReset((int)$user['id'], $ttl);
                    $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                    $link = $host . dirname($_SERVER['SCRIPT_NAME']) . '/reinitialiser_mot_de_passe.php?token=' . urlencode($data['token']);
                    PasswordResetManager::logMailStub($email, 'EcoRide — Réinitialisation du mot de passe', $link);
                }
                // UX: rediriger vers la page de connexion avec bannière
                $_SESSION['reset_info'] = $info;
                header('Location: connexion.php');
                exit;
            } catch (Throwable $e) {
                $error = "Erreur interne. Veuillez réessayer.";
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
    <title>Mot de passe oublié — EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <?php $page_title='Mot de passe oublié — EcoRide'; $page_desc="Générez un lien de réinitialisation pour votre compte."; require __DIR__ . '/includes/layout/seo.php'; ?>
</head>
<body class="bg-light">
<?php require __DIR__ . '/includes/layout/navbar.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card p-4 shadow-lg">
                <h1 class="h4 mb-3">Mot de passe oublié</h1>
                <p class="text-muted">Saisissez votre email. Un lien de réinitialisation sera généré et consigné (démo).</p>

                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($info): ?><div class="alert alert-success"><?= htmlspecialchars($info) ?></div><?php endif; ?>
                

                <form method="POST" action="mot_de_passe_oublie.php" class="row g-3">
                    <?= class_exists('Csrf') ? Csrf::input() : '' ?>
                    <div class="col-12">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success w-100">Générer un lien</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>
</body>
</html>
