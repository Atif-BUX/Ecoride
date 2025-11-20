<?php
// SEO + Favicons shared snippet for inclusion inside <head>.
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$current = $scheme . '://' . $host . ($_SERVER['REQUEST_URI'] ?? '/');
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
$site = rtrim($scheme . '://' . $host . ($basePath === '' ? '' : $basePath), '/');

$defaultDesc = "EcoRide — plateforme de covoiturage écoresponsable. Recherchez, proposez et partagez vos trajets simplement tout en réduisant votre empreinte carbone.";
$desc = isset($page_desc) && is_string($page_desc) && $page_desc !== '' ? $page_desc : $defaultDesc;

$ogTitle = isset($page_title) && is_string($page_title) && $page_title !== '' ? $page_title : 'EcoRide — Covoiturage écoresponsable';
$ogImage = (isset($page_image) && is_string($page_image) && $page_image !== '') ? $page_image : 'graphics/ecoride-en-5-points.jpg';
?>
<meta name="description" content="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>">
<link rel="canonical" href="<?= htmlspecialchars($current, ENT_QUOTES, 'UTF-8') ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:url" content="<?= htmlspecialchars($current, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">

<!-- Favicons -->
<link rel="icon" href="favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
<meta name="theme-color" content="#1e8449">
<link rel="manifest" href="manifest.webmanifest">

<!-- Structured Data (JSON-LD) -->
<?php
$org = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'EcoRide',
    'url' => $site . '/',
    'logo' => $site . '/ico.png',
    'sameAs' => [
        'https://github.com/Atif-BUX/Studi'
    ]
];

$website = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'EcoRide',
    'url' => $site . '/',
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => $site . '/covoiturages.php?depart={search_term_string}',
        'query-input' => 'required name=search_term_string'
    ]
];
?>
<script type="application/ld+json">
<?= json_encode($org, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
<script type="application/ld+json">
<?= json_encode($website, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
