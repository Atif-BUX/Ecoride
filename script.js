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


// --- 3. Validation du Formulaire de Publication de Trajet (NOUVEAU) ---
function validateTrajetForm() {
    let isValid = true;

    // Récupération des champs
    const departInput = document.getElementById('depart');
    const arriveeInput = document.getElementById('arrivee');
    const dateInput = document.getElementById('date');
    const heureInput = document.getElementById('heure');
    const placesInput = document.getElementById('places');
    const prixInput = document.getElementById('prix');

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


// --- 4. Validation du Formulaire de Connexion (EXISTANT) ---
function validateConnexionForm() {
    let isValid = true;

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    // Réinitialisation des erreurs (via la fonction utilitaire)
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


// --- 2. LOGIQUE GLOBALE (Événements et GSAP) ---
document.addEventListener('DOMContentLoaded', function() {

    // --- LOGIQUE GSAP ET ANIMATIONS DE LA PAGE D'ACCUEIL ---

    const logo = document.querySelector('#logo-animation');
    const slogan = document.querySelector('.text-center h1');

    // Animation du logo et du slogan EcoRide design
    if (logo && slogan) {
        const mainTimeline = gsap.timeline({ defaults: { ease: "power2.out" } });
        mainTimeline
            .from(logo, { duration: 1, opacity: 0, scale: 0.5 })
            .from(slogan, { duration: 1.5, opacity: 0, y: -50 }, "-=0.5");

        mainTimeline.eventCallback("onComplete", () => {
            gsap.to("#logo-animation", {
                duration: 5,
                rotation: 360,
                ease: "none",
                repeat: -1,
                transformOrigin: "50% 50%"
            });
        });
    }

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

    // --- LOGIQUE DE VALIDATION DES FORMULAIRES ---

    // 1. Connexion (ID: connexionForm)
    const formConnexion = document.getElementById('connexionForm');
    if (formConnexion) {
        formConnexion.addEventListener('submit', function(event) {
            event.preventDefault();
            if (validateConnexionForm()) {
                alert("Connexion réussie côté client ! Envoi au serveur simulé.");
                // formConnexion.submit();
            }
        });
    }

    // 2. Publication de Trajet (ID: trajetForm)
    const formTrajet = document.getElementById('trajetForm');
    if (formTrajet) {
        formTrajet.addEventListener('submit', function(event) {
            event.preventDefault();
            if (validateTrajetForm()) {
                alert("Trajet publié côté client ! Envoi au serveur simulé.");
                // formTrajet.submit();
            }
        });
    }
});