<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout/header.php';
?>
<main class="container py-5">
  <h1 class="mb-4">Politique de confidentialité</h1>

  <section class="mb-4">
    <h2 class="h5">Introduction</h2>
    <p>Cette politique de confidentialité explique quelles données personnelles nous collectons, comment nous les utilisons et vos droits à ce sujet. EcoRide s'engage à protéger la vie privée de ses utilisateurs conformément à la réglementation applicable.</p>
  </section>

  <section class="mb-4">
    <h2 class="h5">Données collectées</h2>
    <ul>
      <li>Informations de compte (nom, prénom, email, mot de passe haché).</li>
      <li>Préférences et informations de profil (téléphone, adresse, pseudo, photo).</li>
      <li>Données liées aux trajets (villes de départ/arrivée, horaires, véhicules).</li>
      <li>Journal des transactions de crédits et réservations.</li>
    </ul>
  </section>

  <section class="mb-4">
    <h2 class="h5">Finalités</h2>
    <ul>
      <li>Fournir le service de covoiturage (recherche, réservation, gestion des trajets).</li>
      <li>Assurer la sécurité (authentification, protection CSRF, prévention de fraude).</li>
      <li>Améliorer l'expérience utilisateur et la qualité du service.</li>
      <li>Respecter nos obligations légales.</li>
    </ul>
  </section>

  <section class="mb-4">
    <h2 class="h5">Base légale</h2>
    <p>Le traitement est fondé sur l'exécution du contrat liant l'utilisateur et EcoRide, ainsi que sur l'intérêt légitime d'EcoRide à assurer la sécurité et l'amélioration du service.</p>
  </section>

  <section class="mb-4">
    <h2 class="h5">Durées de conservation</h2>
    <p>Les données sont conservées pendant la durée nécessaire à la fourniture du service et au respect de nos obligations légales, puis supprimées ou anonymisées.</p>
  </section>

  <section class="mb-4">
    <h2 class="h5">Vos droits</h2>
    <ul>
      <li>Droit d'accès, de rectification et d'effacement de vos données.</li>
      <li>Droit d'opposition et de limitation du traitement.</li>
      <li>Droit à la portabilité des données.</li>
      <li>Pour exercer vos droits, contactez : <a href="mailto:contact@ecoride.fr">contact@ecoride.fr</a></li>
    </ul>
  </section>

  <section class="mb-4">
    <h2 class="h5">Cookies</h2>
    <p>Des cookies techniques sont utilisés pour assurer le bon fonctionnement (session, CSRF). Aucun cookie publicitaire n'est déposé sans votre consentement.</p>
  </section>

  <section class="mb-4">
    <h2 class="h5">Sécurité</h2>
    <p>Nous mettons en œuvre des mesures de sécurité appropriées (mots de passe hachés, protections CSRF, validations côté serveur).</p>
  </section>

  <section class="mb-4">
    <h2 class="h5">Contact</h2>
    <p>Pour toute question relative à cette politique, écrivez à <a href="mailto:contact@ecoride.fr">contact@ecoride.fr</a>.</p>
  </section>
</main>
<?php require __DIR__ . '/includes/layout/footer.php'; ?>

