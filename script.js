// Fichier : script.js - 20/11/25

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

    // 5. Recherche asynchrone des trajets (Fetch API)
    const searchForm = document.getElementById('travelSearchForm');
    const resultsContainer = document.getElementById('searchResults');
    const statusAlert = document.getElementById('searchStatus');

    if (searchForm && resultsContainer && statusAlert && typeof window.fetch === 'function') {
        let csrfField = '';
        if (resultsContainer.dataset.csrfField) {
            try {
                csrfField = atob(resultsContainer.dataset.csrfField);
            } catch (e) {
                csrfField = '';
            }
        }
        const isAuth = resultsContainer.dataset.isAuth === '1';

        searchForm.addEventListener('submit', function(event) {
            if (!validateSearchForm()) {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            runAsyncTravelSearch(searchForm, resultsContainer, statusAlert, csrfField, isAuth);
        });
    }
});

function runAsyncTravelSearch(form, container, statusAlert, csrfField, isAuth) {
    const formData = new FormData(form);
    const query = new URLSearchParams(formData);

    showStatus(statusAlert, 'Recherche en cours...', 'info');

    fetch('api/search_travels.php?' + query.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Requête invalide');
            }
            return response.json();
        })
        .then(payload => {
            renderAsyncResults(container, payload, csrfField, isAuth, formData);
            if (payload.message) {
                showStatus(statusAlert, payload.message, (payload.travels?.length || payload.fallback?.length) ? 'info' : 'warning');
            } else {
                hideStatus(statusAlert);
            }
        })
        .catch(() => {
            showStatus(statusAlert, 'Erreur lors de la recherche. Veuillez réessayer.', 'danger');
        });
}

function renderAsyncResults(container, payload, csrfField, isAuth, formData) {
    if (!container) {
        return;
    }

    container.innerHTML = '';
    const travels = Array.isArray(payload?.travels) ? payload.travels : [];
    const fallback = Array.isArray(payload?.fallback) ? payload.fallback : [];

    if (!travels.length && !fallback.length) {
        container.appendChild(buildEmptyState(csrfField, isAuth, formData));
        return;
    }

    if (travels.length) {
        const fragment = document.createDocumentFragment();
        travels.forEach(travel => fragment.appendChild(buildTravelCard(travel)));
        container.appendChild(fragment);
    }

    if (fallback.length) {
        const wrapper = document.createElement('div');
        wrapper.className = 'mt-4';

        const title = document.createElement('h3');
        title.className = 'h5 text-muted';
        title.textContent = 'Suggestions proches';
        wrapper.appendChild(title);

        const fragment = document.createDocumentFragment();
        fallback.forEach(travel => fragment.appendChild(buildTravelCard(travel)));
        wrapper.appendChild(fragment);
        container.appendChild(wrapper);
    }
}

function buildTravelCard(travel) {
    const link = document.createElement('a');
    link.href = travel.detail_url || '#';
    link.className = 'card mb-4 shadow-sm search-tool-card text-decoration-none d-block p-3';

    const layout = document.createElement('div');
    layout.className = 'd-flex justify-content-between align-items-center';
    link.appendChild(layout);

    const driverWrapper = document.createElement('div');
    driverWrapper.className = 'd-flex align-items-center';
    layout.appendChild(driverWrapper);

    if (travel.photo) {
        const photo = document.createElement('img');
        photo.src = travel.photo;
        photo.alt = 'Photo conducteur';
        photo.className = 'rounded-circle me-3';
        photo.style.width = '50px';
        photo.style.height = '50px';
        photo.style.objectFit = 'cover';
        photo.style.border = '2px solid var(--color-primary-light)';
        driverWrapper.appendChild(photo);
    } else {
        const icon = document.createElement('i');
        icon.className = 'fas fa-user fa-2x rounded-circle me-3 p-2 d-flex justify-content-center align-items-center';
        icon.style.width = '50px';
        icon.style.height = '50px';
        icon.style.color = 'var(--color-primary-dark)';
        icon.style.backgroundColor = 'var(--color-primary-light)';
        driverWrapper.appendChild(icon);
    }

    const driverInfo = document.createElement('div');
    driverWrapper.appendChild(driverInfo);

    const driverName = document.createElement('p');
    driverName.className = 'mb-0 fw-bold';
    driverName.style.color = 'var(--color-primary-dark)';
    driverName.textContent = travel.driver || 'Conducteur';
    driverInfo.appendChild(driverName);

    const routeLine = document.createElement('p');
    routeLine.className = 'mb-0 text-muted small';
    routeLine.textContent = `${travel.time_label || ''} – ${travel.departure_city || ''} → ${travel.arrival_city || ''}`;
    driverInfo.appendChild(routeLine);

    if (travel.date_label) {
        const dateLine = document.createElement('p');
        dateLine.className = 'mb-0 text-muted small';
        dateLine.textContent = `Départ le ${travel.date_label}`;
        driverInfo.appendChild(dateLine);
    }

    if (travel.description) {
        const desc = document.createElement('p');
        desc.className = 'mb-0 text-muted small fst-italic';
        desc.textContent = travel.description;
        driverInfo.appendChild(desc);
    }

    if (travel.car_details) {
        const car = document.createElement('p');
        car.className = 'mb-0 text-muted small';
        car.innerHTML = '<i class=\"fas fa-car-side me-1\"></i> Véhicule: ';
        const carText = document.createElement('span');
        carText.textContent = travel.car_details;
        car.appendChild(carText);
        driverInfo.appendChild(car);
    }

    const priceWrapper = document.createElement('div');
    priceWrapper.className = 'text-end';
    layout.appendChild(priceWrapper);

    const badge = document.createElement('span');
    badge.className = 'badge rounded-pill fs-5 p-2';
    badge.style.backgroundColor = 'var(--color-primary-light)';
    badge.style.color = 'var(--color-neutral-white)';
    badge.textContent = `${formatPrice(travel.price_per_seat)} €`;
    priceWrapper.appendChild(badge);

    const seats = document.createElement('p');
    seats.className = 'text-muted small mb-0';
    seats.textContent = `${travel.available_seats || 0} place(s)`;
    priceWrapper.appendChild(seats);

    return link;
}

function buildEmptyState(csrfField, isAuth, formData) {
    const wrapper = document.createElement('div');
    wrapper.className = 'alert alert-info text-center py-4 my-3';
    wrapper.setAttribute('role', 'alert');

    const departureValue = escapeHtml(formData.get('depart') || '');
    const arrivalValue = escapeHtml(formData.get('arrivee') || '');
    const dateValue = escapeHtml(formData.get('date_depart') || '');

    wrapper.innerHTML = `
        <h4 class=\"alert-heading\">Aucun trajet trouvé 😔</h4>
        <p>Désolé, nous n'avons trouvé aucun trajet correspondant à votre recherche.</p>
        <hr>
        <p class=\"mb-2\"><strong>Vous êtes passager ?</strong></p>
        <div class=\"card card-body text-start\">
            <form method=\"post\" action=\"covoiturages.php\" class=\"row g-2\">
                <input type=\"hidden\" name=\"action\" value=\"express_need\">
                ${csrfField || ''}
                <div class=\"col-12 col-md-4\">
                    <label for=\"need_depart_js\" class=\"form-label\">Départ</label>
                    <input type=\"text\" id=\"need_depart_js\" name=\"depart\" class=\"form-control\" value=\"${departureValue}\" required>
                </div>
                <div class=\"col-12 col-md-4\">
                    <label for=\"need_arrivee_js\" class=\"form-label\">Arrivée</label>
                    <input type=\"text\" id=\"need_arrivee_js\" name=\"arrivee\" class=\"form-control\" value=\"${arrivalValue}\" required>
                </div>
                <div class=\"col-12 col-md-4\">
                    <label for=\"need_date_js\" class=\"form-label\">Date souhaitée</label>
                    <input type=\"date\" id=\"need_date_js\" name=\"date_depart\" class=\"form-control\" value=\"${dateValue}\">
                </div>
                <div class=\"col-12\">
                    <label for=\"need_note_js\" class=\"form-label\">Message (optionnel)</label>
                    <textarea id=\"need_note_js\" name=\"note\" class=\"form-control\" rows=\"2\" placeholder=\"Précisez vos contraintes ou préférences\"></textarea>
                </div>
                <div class=\"col-12\">
                    <button type=\"submit\" class=\"btn btn-success\">Enregistrer ma demande</button>
                </div>
            </form>
        </div>
        <p class=\"mb-0\">
            <strong>Vous êtes conducteur ?</strong>
            ${isAuth ? '<a href=\"proposer_trajet.php\" class=\"alert-link fw-bold\" style=\"color: var(--color-primary-dark);\">Proposez votre trajet maintenant</a>' : '<a href=\"connexion.php\" class=\"alert-link fw-bold\" style=\"color: var(--color-primary-dark);\">Connectez-vous</a> pour proposer un trajet.'}
        </p>
    `;

    return wrapper;
}

function showStatus(element, message, type) {
    if (!element) {
        return;
    }
    element.textContent = message;
    element.className = 'alert alert-' + type;
    element.classList.remove('d-none');
}

function hideStatus(element) {
    if (!element) {
        return;
    }
    element.classList.add('d-none');
}

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function(match) {
        switch (match) {
            case '&':
                return '&amp;';
            case '<':
                return '&lt;';
            case '>':
                return '&gt;';
            case '"':
                return '&quot;';
            case "'":
                return '&#039;';
            default:
                return match;
        }
    });
}

function formatPrice(value) {
    try {
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value || 0);
    } catch (e) {
        return (value || 0).toFixed(2);
    }
}
