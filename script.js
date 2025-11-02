// Fichier : script.js

// 1. Initialisation GSAP
gsap.registerPlugin(ScrollTrigger);

// Fonction utilitaire pour afficher les erreurs
function displayError(inputElement, message) {
    const errorElement = document.getElementById(inputElement.id + 'Error');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    inputElement.classList.add('is-invalid');
}


// --- 3. Validation du Formulaire de Publication de Trajet ---
function validateTrajetForm() {
    let isValid = true;

    // Récupération des champs - LES IDs SONT MAINTENANT CORRECTS
    const departInput = document.getElementById('depart');
    const arriveeInput = document.getElementById('arrivee');
    const dateInput = document.getElementById('date_depart'); // Était 'date'
    const heureInput = document.getElementById('heure_depart'); // Était 'heure'
    const placesInput = document.getElementById('seats'); // Était 'places'
    const prixInput = document.getElementById('price'); // Était 'prix'

    // Réinitialisation des erreurs
    const inputs = [departInput, arriveeInput, dateInput, heureInput, placesInput, prixInput];
    inputs.forEach(input => {
        const errorElement = document.getElementById(input.id + 'Error');
        if(errorElement) errorElement.style.display = 'none';
        input.classList.remove('is-invalid');
    });

    // 1. Départ & Arrivée
    if (departInput.value.trim() === '') {
        displayError(departInput, 'La ville de départ est obligatoire.');
        isValid = false;
    }
    if (arriveeInput.value.trim() === '') {
        displayError(arriveeInput, 'La ville d\'arrivée est obligatoire.');
        isValid = false;
    }
    if (departInput.value.trim() !== '' && departInput.value.trim() === arriveeInput.value.trim()) {
        displayError(arriveeInput, 'La ville de départ ne peut pas être la même que la ville d\'arrivée.');
        isValid = false;
    }

    // 2. Date (Doit être aujourd'hui ou dans le futur)
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    const selectedDate = new Date(dateInput.value);

    if (dateInput.value === '') {
        displayError(dateInput, 'La date du trajet est obligatoire.');
        isValid = false;
    } else if (selectedDate < now) {
        displayError(dateInput, 'La date doit être aujourd\'hui ou dans le futur.');
        isValid = false;
    }

    // 3. Heure
    if (heureInput.value === '') {
        displayError(heureInput, 'L\'heure de départ est obligatoire.');
        isValid = false;
    }

    // 4. Places
    const placesValue = parseInt(placesInput.value);
    if (isNaN(placesValue) || placesValue < 1 || placesValue > 8) {
        displayError(placesInput, 'Veuillez indiquer un nombre de places valide (entre 1 et 8).');
        isValid = false;
    }

    // 5. Prix
    const prixValue = parseFloat(prixInput.value);
    if (isNaN(prixValue) || prixValue <= 0) {
        displayError(prixInput, 'Le prix doit être un nombre positif (supérieur à 0 €).');
        isValid = false;
    }

    return isValid;
}


// --- 4. Validation du Formulaire de Connexion ---
function validateConnexionForm() {
    let isValid = true;

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    // Réinitialisation des erreurs
    const inputs = [emailInput, passwordInput];
    inputs.forEach(input => {
        const errorElement = document.getElementById(input.id + 'Error');
        if(errorElement) errorElement.style.display = 'none';
        input.classList.remove('is-invalid');
    });

    // Validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (emailInput.value.trim() === '') {
        displayError(emailInput, 'L\'adresse email est obligatoire.');
        isValid = false;
    } else if (!emailRegex.test(emailInput.value.trim())) {
        displayError(emailInput, 'Veuillez entrer une adresse email valide.');
        isValid = false;
    }

    if (passwordInput.value === '') {
        displayError(passwordInput, 'Le mot de passe est obligatoire.');
        isValid = false;
    }

    return isValid;
}


// --- 5. Validation du Formulaire de Profil ---
function validateProfilForm() {
    let isValid = true;

    const pseudoInput = document.getElementById('pseudo');
    const emailInput = document.getElementById('emailProfil');
    const telephoneInput = document.getElementById('telephone');

    // Réinitialisation des erreurs
    const inputs = [pseudoInput, emailInput, telephoneInput];
    inputs.forEach(input => {
        const errorElement = document.getElementById(input.id + 'Error');
        if(errorElement) errorElement.style.display = 'none';
        input.classList.remove('is-invalid');
    });

    // 1. Pseudo (Obligatoire)
    if (pseudoInput.value.trim() === '') {
        displayError(pseudoInput, 'Le pseudo est obligatoire.');
        isValid = false;
    }

    // 2. Email (Obligatoire et format)
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (emailInput.value.trim() === '') {
        displayError(emailInput, 'L\'adresse email est obligatoire.');
        isValid = false;
    } else if (!emailRegex.test(emailInput.value.trim())) {
        displayError(emailInput, 'Veuillez entrer une adresse email valide.');
        isValid = false;
    }

    // 3. Téléphone (Optionnel, mais valide si renseigné)
    const phoneRegex = /^(?:0|\+33|\(\+33\))[1-9](?:[\s.-]*\d{2}){4}$/;

    if (telephoneInput.value.trim() !== '' && !phoneRegex.test(telephoneInput.value.trim())) {
        displayError(telephoneInput, 'Veuillez entrer un numéro de téléphone valide (ex: 06 12 34 56 78).');
        isValid = false;
    }

    return isValid;
}


// --- 6. Validation du Formulaire de Recherche ---
function validateSearchForm() {
    let isValid = true;

    // Récupération des champs
    const departInput = document.getElementById('searchDepart');
    const arriveeInput = document.getElementById('searchArrivee');
    const dateInput = document.getElementById('searchDate');

    // Réinitialisation des erreurs
    const inputs = [departInput, arriveeInput, dateInput];
    inputs.forEach(input => {
        const errorElement = document.getElementById(input.id + 'Error');
        if(errorElement) errorElement.style.display = 'none';
        input.classList.remove('is-invalid');
    });

    // 1. Départ & Arrivée
    if (departInput.value.trim() === '') {
        displayError(departInput, 'Le départ est obligatoire.');
        isValid = false;
    }
    if (arriveeInput.value.trim() === '') {
        displayError(arriveeInput, 'L\'arrivée est obligatoire.');
        isValid = false;
    }
    if (departInput.value.trim() !== '' && departInput.value.trim() === arriveeInput.value.trim()) {
        displayError(arriveeInput, 'Départ et arrivée ne peuvent pas être identiques.');
        isValid = false;
    }

    // 2. Date (Doit être aujourd'hui ou dans le futur)
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    const selectedDate = new Date(dateInput.value);

    if (dateInput.value === '') {
        displayError(dateInput, 'La date est obligatoire.');
        isValid = false;
    } else if (selectedDate < now) {
        displayError(dateInput, 'La date doit être aujourd\'hui ou dans le futur.');
        isValid = false;
    }

    return isValid;
}


// -----------------------------------------------------------------------
// --- LOGIQUE GLOBALE (Événements et GSAP) ---
// -----------------------------------------------------------------------
function __runOnReady(cb) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', cb);
    } else {
        cb();
    }
}

__runOnReady(function() {

    // --- LOGIQUE GSAP GLOBALE (ANIMATION DU LOGO) ---
    // Cette partie est exécutée sur TOUTES les pages ayant l'ID #logo-animation
    const logoElement = document.getElementById('logo-animation');

    if (logoElement) {
        // Timeline d'entrée (opacity et scale)
        const logoTimeline = gsap.timeline({ defaults: { ease: "power2.out" } });

        logoTimeline
            .from(logoElement, { duration: 1, opacity: 0, scale: 0.5 })
            .eventCallback("onComplete", () => {
                // Rotation infinie après l'entrée
                gsap.to(logoElement, {
                    duration: 5,
                    rotation: 360,
                    ease: "none",
                    repeat: -1,
                    transformOrigin: "50% 50%"
                });
            });
    }

    // --- LOGIQUE GSAP SPÉCIFIQUE À LA PAGE D'ACCUEIL (index.php) ---
    // Les animations ne se lancent que si le slogan est présent
    const slogan = document.querySelector('#slogan-animation'); // Utilisation de l'ID que nous avons ajouté

    if (slogan) {
        // Animation du Slogan
        gsap.from(slogan, { duration: 1.5, opacity: 0, y: -50, ease: "power2.out", delay: 0.5 }); // Décalage pour après l'entrée du logo

        // Animation des images avec ScrollTrigger
        let mm = gsap.matchMedia();

        mm.add("(min-width: 768px)", () => {
            gsap.timeline({
                scrollTrigger: { trigger: ".image-gallery", start: "top 80%" }
            })
                .from(".image-gallery .col-md-4:nth-child(1) img", { opacity: 0, x: -100, duration: 1 })
                .from(".image-gallery .col-md-4:nth-child(2) img", { opacity: 0, y: 50, duration: 1 }, "-=0.5")
                .from(".image-gallery .col-md-4:nth-child(3) img", { opacity: 0, x: 100, duration: 1 }, "-=0.5");
        });

        mm.add("(max-width: 767px)", () => {
            gsap.timeline({
                scrollTrigger: { trigger: ".image-gallery", start: "top 80%" }
            })
                .from(".image-gallery .col-md-4:nth-child(1) img", { opacity: 0, x: -100, duration: 1 })
                .from(".image-gallery .col-md-4:nth-child(2) img", { opacity: 0, x: 100, duration: 1 }, "-=0.5")
                .from(".image-gallery .col-md-4:nth-child(3) img", { opacity: 0, x: -100, duration: 1 }, "-=0.5");
        });

        // Animation de la section de recherche
        gsap.from(".search-tool-card", {
            duration: 1.5,
            y: 50,
            opacity: 0,
            ease: "power2.out",
            scrollTrigger: {
                trigger: ".search-tool-card",
                start: "top 90%",
                toggleActions: "play none none none"
            }
        });
    }


    // --- LOGIQUE DE VALIDATION DES FORMULAIRES ---

    // 1. Recherche (ID: searchForm)
    const formSearch = document.getElementById('searchForm');
    if (formSearch) {
        formSearch.addEventListener('submit', function(event) {
            event.preventDefault();
            if (validateSearchForm()) {
                formSearch.submit(); // Envoi réel si la validation est OK
            }
        });
    }

// 2. Connexion (ID: connexionForm)
    const formConnexion = document.getElementById('connexionForm');
    if (formConnexion) {
        formConnexion.addEventListener('submit', function(event) {
            event.preventDefault(); // <-- LAISSEZ CETTE LIGNE
            if (validateConnexionForm()) {
                // CETTE LIGNE DOIT ÊTRE COMMENTÉE OU SUPPRIMÉE :
                // alert("Connexion réussie côté client ! Envoi au serveur simulé.");

                // CETTE LIGNE DOIT ÊTRE DÉCOMMENTÉE ET PRÉSENTE :
                formConnexion.submit();
            }
        });
    }
    // 3. Publication de Trajet (ID: trajetForm)
    const formTrajet = document.getElementById('trajetForm');
    if (formTrajet) {
        formTrajet.addEventListener('submit', function(event) {

            // 🛑 TRÈS IMPORTANT : Ceci évite la boucle infinie de soumission
            if (this.classList.contains('js-submitting')) return;

            event.preventDefault(); // On garde le preventDefault() pour lancer la validation JS

            if (validateTrajetForm()) {

                // 1. Marquer le formulaire pour éviter la boucle infinie
                this.classList.add('js-submitting');

                // 2. Soumission réelle (celle-ci est la bonne !)
                this.submit();
            }
        });
    }

    // 4. Profil Utilisateur (ID: profilForm)
    const formProfil = document.getElementById('profilForm');
    if (formProfil) {
        formProfil.addEventListener('submit', function(event) {
            event.preventDefault();
            if (validateProfilForm()) {
                alert("Profil mis à jour côté client ! Envoi au serveur simulé.");
                // formProfil.submit();
            }
        });
    }
});
