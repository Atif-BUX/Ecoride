<?php
// Fichier: pourquoi_ecoride.php — Page marketing « Pourquoi choisir EcoRide »
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pourquoi choisir EcoRide ?</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <?php $page_title='Pourquoi choisir EcoRide ?'; $page_desc="5 raisons d\'adopter EcoRide : simplicité, écologie, économie, communauté et flexibilité."; $page_image='graphics/ecoride-en-5-points.jpg'; require __DIR__ . '/includes/layout/seo.php'; ?>
    <style>
        .hero-wrap {
            position: relative;
            overflow: hidden;
            border-bottom: 4px solid rgba(0,0,0,0.06);
        }
        .hero-image {
            width: 100%;
            height: 320px;
            object-fit: cover;
            filter: saturate(1.05) contrast(1.05);
        }
        @media (min-width: 992px) {
            .hero-image { height: 420px; }
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.35) 0%, rgba(0,0,0,0.45) 100%);
            display: flex;
            align-items: center;
        }
        .hero-title {
            color: #fff;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .pill {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            color: #fff;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            letter-spacing: .2px;
        }
        .benefit i { color: var(--color-primary-light); }
    </style>
    </head>
<body class="bg-light">

<?php require __DIR__ . '/includes/layout/navbar.php'; ?>

<main>
    <section class="hero-wrap">
        <img src="graphics/ecoRide-en-5-points.webp" class="hero-image" alt="EcoRide en 5 points clés">
        <div class="hero-overlay">
            <div class="container">
                <div class="col-lg-8">
                    <span class="pill mb-2">Covoiturage écoresponsable</span>
                    <h1 class="display-5 fw-bold hero-title mb-2">Pourquoi choisir EcoRide pour vos trajets ?</h1>
                    <p class="text-white-50 mb-0">Des déplacements plus simples, plus verts et plus humains.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <p class="lead mb-4">
                    Chez EcoRide, nous avons réinventé le covoiturage autour de 5 avantages clés et de fonctionnalités
                    innovantes pour rendre vos déplacements plus simples, plus vertueux et plus humains.
                </p>

                <div class="card search-tool-card p-4 mb-4">
                    <div class="benefit d-flex">
                        <div class="me-3"><i class="fas fa-magic fa-xl"></i></div>
                        <div>
                            <h2 class="h5 fw-bold mb-1">1. Un outil simple et intuitif</h2>
                            <p class="mb-0">
                                Trouver ou proposer un covoiturage n’a jamais été aussi facile : indiquez votre départ,
                                votre arrivée et votre date de trajet, puis lancez la recherche. Un espace personnalisé
                                vous permet de vous inscrire ou de vous connecter pour accéder à toutes les fonctionnalités.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card search-tool-card p-4 mb-4">
                    <div class="benefit d-flex">
                        <div class="me-3"><i class="fas fa-leaf fa-xl"></i></div>
                        <div>
                            <h2 class="h5 fw-bold mb-1">2. Mobilité écologique</h2>
                            <p class="mb-0">
                                Chaque trajet partagé, c’est moins de pollution : EcoRide vous aide à réduire vos
                                émissions de CO<sub>2</sub> en privilégiant le transport collaboratif. Un engagement
                                fort pour la planète et les générations futures.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card search-tool-card p-4 mb-4">
                    <div class="benefit d-flex">
                        <div class="me-3"><i class="fas fa-wallet fa-xl"></i></div>
                        <div>
                            <h2 class="h5 fw-bold mb-1">3. Avantages économiques</h2>
                            <p class="mb-0">
                                En covoiturant via EcoRide, vous divisez les frais d’essence, de péage et de
                                stationnement. Pour les conducteurs, c’est aussi un moyen d’amortir les coûts liés à la
                                voiture ; pour les passagers, des trajets à prix mini.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card search-tool-card p-4 mb-4">
                    <div class="benefit d-flex">
                        <div class="me-3"><i class="fas fa-users fa-xl"></i></div>
                        <div>
                            <h2 class="h5 fw-bold mb-1">4. Dimension sociale et communautaire</h2>
                            <p class="mb-0">
                                Voyagez en bonne compagnie et faites de belles rencontres. EcoRide favorise la création
                                de liens et le partage : chaque trajet devient une expérience enrichissante. Notre
                                communauté active propose aussi de l’aide au chargement des bagages pour une entraide
                                réelle entre membres.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card search-tool-card p-4 mb-4">
                    <div class="benefit d-flex">
                        <div class="me-3"><i class="fas fa-route fa-xl"></i></div>
                        <div>
                            <h2 class="h5 fw-bold mb-1">5. Accessible partout et pour tous les besoins</h2>
                            <p class="mb-0">
                                Covoiturage en ville, pour des trajets réguliers ou longue distance, EcoRide s’adapte à
                                toutes vos mobilités. Rejoignez‑nous pour des déplacements flexibles, adaptés à votre
                                quotidien et vos envies de voyage.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Mini FAQ -->
                <div class="card search-tool-card p-4 mb-4">
                    <h2 class="h4 fw-bold mb-3"><i class="fas fa-question-circle me-2"></i>Mini‑FAQ</h2>
                    <div class="accordion" id="faqEcoRide">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="q1">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#a1" aria-expanded="true" aria-controls="a1">
                                    Comment réserver un covoiturage sur EcoRide ?
                                </button>
                            </h2>
                            <div id="a1" class="accordion-collapse collapse show" aria-labelledby="q1" data-bs-parent="#faqEcoRide">
                                <div class="accordion-body">
                                    Renseignez la ville de départ, d’arrivée et la date, puis lancez la recherche.
                                    Sélectionnez un trajet et suivez les étapes de réservation. Votre espace utilisateur
                                    vous permet ensuite de confirmer ou d’annuler si nécessaire.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="q2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2" aria-expanded="false" aria-controls="a2">
                                    Puis‑je annuler une réservation ?
                                </button>
                            </h2>
                            <div id="a2" class="accordion-collapse collapse" aria-labelledby="q2" data-bs-parent="#faqEcoRide">
                                <div class="accordion-body">
                                    Oui. Tant que le trajet n’a pas démarré, vous pouvez annuler via votre Profil. Si
                                    la réservation est confirmée, EcoRide remet automatiquement la place à disposition et
                                    effectue l’ajustement des crédits selon le statut et le moment de l’annulation.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="q3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a3" aria-expanded="false" aria-controls="a3">
                                    Comment fonctionnent les crédits EcoRide ?
                                </button>
                            </h2>
                            <div id="a3" class="accordion-collapse collapse" aria-labelledby="q3" data-bs-parent="#faqEcoRide">
                                <div class="accordion-body">
                                    À l’inscription, vous recevez des crédits. Lors d’une confirmation de réservation,
                                    les crédits sont débités côté passager et, à la fin du trajet, les gains sont
                                    crédités côté conducteur. Une petite commission plateforme peut s’appliquer.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="q4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a4" aria-expanded="false" aria-controls="a4">
                                    EcoRide favorise‑t‑il les véhicules électriques ?
                                </button>
                            </h2>
                            <div id="a4" class="accordion-collapse collapse" aria-labelledby="q4" data-bs-parent="#faqEcoRide">
                                <div class="accordion-body">
                                    Oui, un filtre permet d’identifier les trajets « éco » et les véhicules électriques.
                                    Nous encourageons les mobilités bas‑carbone et l’entraide pour le covoiturage du
                                    quotidien.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="q5">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a5" aria-expanded="false" aria-controls="a5">
                                    Faut‑il créer un compte pour proposer un trajet ?
                                </button>
                            </h2>
                            <div id="a5" class="accordion-collapse collapse" aria-labelledby="q5" data-bs-parent="#faqEcoRide">
                                <div class="accordion-body">
                                    Oui. Créez un compte gratuitement, complétez votre profil conducteur (véhicule,
                                    préférences) puis proposez votre trajet en quelques clics depuis la page dédiée.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <a href="covoiturages.php" class="btn btn-success btn-lg px-4">
                        <i class="fas fa-search me-2"></i> Découvrir les trajets
                    </a>
                    <?php if (empty($_SESSION['is_logged_in'])): ?>
                        <a href="inscription.php" class="btn btn-outline-success btn-lg px-4 ms-2">Créer un compte</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>

</body>
</html>
