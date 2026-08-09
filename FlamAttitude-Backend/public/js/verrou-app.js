/**
 * Verrou local de l'app mobile pour le staff/admin : à chaque ouverture ou retour
 * au premier plan, demande Face ID/empreinte (ou mot de passe en repli) avant de
 * laisser voir le contenu. Ne fait rien sur un navigateur classique, ni pour les clients.
 */
(function () {
    if (typeof Capacitor === 'undefined' || !Capacitor.isNativePlatform()) {
        return;
    }

    if (!window.FLAMATTITUDE_STAFF) {
        return;
    }

    var NativeBiometric = Capacitor.Plugins.NativeBiometric;
    var App = Capacitor.Plugins.App;

    // Empêche la boucle infinie : l'ouverture/fermeture de la fenêtre biométrique
    // native déclenche elle-même un "retour au premier plan", qu'il ne faut pas
    // interpréter comme un vrai retour de l'utilisateur dans l'app.
    var verificationEnCours = false;
    var dernierDeverrouillage = 0;
    var DELAI_GRACE_MS = 1500;

    // Ceci est une app multi-pages classique (chaque clic dans la barre de navigation
    // recharge une page entière) : ce script se ré-exécute donc depuis le début à
    // chaque clic. Pour ne pas redemander la biométrie à chaque navigation interne,
    // on retient "déjà déverrouillé" pour la durée de vie de l'app (sessionStorage,
    // remis à zéro si l'app est vraiment fermée puis rouverte). Le retour au premier
    // plan (appStateChange) ignore volontairement ce drapeau : lui doit toujours reverrouiller.
    function appDejaDeverrouilleeCetteSession() {
        try {
            return sessionStorage.getItem('flamattitudeAppDeverrouillee') === '1';
        } catch (e) {
            return false;
        }
    }

    function marquerAppDeverrouillee() {
        try {
            sessionStorage.setItem('flamattitudeAppDeverrouillee', '1');
        } catch (e) { /* stockage indisponible, tant pis */ }
    }

    // Ouvrir un sélecteur de fichier (photo de profil, document...) fait passer l'app
    // en arrière-plan le temps que l'utilisateur choisisse dans la galerie/l'appareil
    // photo Android, ce qui déclenche un "retour au premier plan" tout à fait normal :
    // il ne faut pas le confondre avec un vrai changement d'app et reverrouiller dessus.
    var ignorerProchainResume = 0;
    var DELAI_IGNORER_RESUME_MS = 2 * 60 * 1000;

    document.addEventListener('click', function (e) {
        if (e.target && e.target.matches && e.target.matches('input[type="file"]')) {
            ignorerProchainResume = Date.now();
        }
    }, true);

    function creerVerrou() {
        if (verificationEnCours || document.getElementById('verrou-app-overlay')) {
            return;
        }

        if (Date.now() - dernierDeverrouillage < DELAI_GRACE_MS) {
            return;
        }

        var overlay = document.createElement('div');
        overlay.id = 'verrou-app-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;background:#5988DA;z-index:99999;'
            + 'display:flex;flex-direction:column;align-items:center;justify-content:center;'
            + 'color:white;font-family:sans-serif;padding:20px;text-align:center;';
        overlay.innerHTML =
            '<h2>Application verrouillée</h2>' +
            '<button id="verrou-biometrie-btn" style="margin:15px 0;padding:12px 24px;border-radius:8px;border:none;background:white;color:#5988DA;font-weight:bold;cursor:pointer;">' +
            'Déverrouiller avec Face ID / empreinte</button>' +
            '<a href="#" id="verrou-afficher-mdp" style="color:white;text-decoration:underline;margin-top:10px;">Utiliser mon mot de passe</a>' +
            '<div id="verrou-mdp-zone" style="display:none;margin-top:15px;">' +
            '<input type="password" id="verrou-mdp-input" placeholder="Mot de passe" style="padding:10px;border-radius:5px;border:none;">' +
            '<button id="verrou-mdp-valider" style="margin-left:8px;padding:10px 16px;border-radius:5px;border:none;background:white;color:#5988DA;font-weight:bold;cursor:pointer;">OK</button>' +
            '<p id="verrou-mdp-erreur" style="color:#ffdddd;margin-top:8px;"></p>' +
            '</div>';

        document.body.appendChild(overlay);

        document.getElementById('verrou-biometrie-btn').addEventListener('click', tenterBiometrie);
        document.getElementById('verrou-afficher-mdp').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('verrou-mdp-zone').style.display = 'block';
        });
        document.getElementById('verrou-mdp-valider').addEventListener('click', validerMotDePasse);

        if (!NativeBiometric) {
            document.getElementById('verrou-mdp-zone').style.display = 'block';
            return;
        }

        NativeBiometric.isAvailable().then(function (resultat) {
            if (resultat.isAvailable) {
                tenterBiometrie();
            } else {
                // Rien d'enregistré sur l'appareil (ni doigt, ni visage) : on ne propose que le mot de passe.
                var bouton = document.getElementById('verrou-biometrie-btn');
                if (bouton) {
                    bouton.style.display = 'none';
                }
                document.getElementById('verrou-mdp-zone').style.display = 'block';
            }
        }).catch(function () {
            document.getElementById('verrou-mdp-zone').style.display = 'block';
        });
    }

    function retirerVerrou() {
        var overlay = document.getElementById('verrou-app-overlay');
        if (overlay) {
            overlay.remove();
        }
        dernierDeverrouillage = Date.now();
        marquerAppDeverrouillee();
    }

    function tenterBiometrie() {
        if (!NativeBiometric || verificationEnCours) {
            return;
        }

        verificationEnCours = true;

        NativeBiometric.verifyIdentity({
            reason: "Déverrouiller Flam'Attitude",
            title: 'Authentification requise',
        }).then(function () {
            verificationEnCours = false;
            retirerVerrou();
        }).catch(function () {
            verificationEnCours = false;
            // Échec ou annulation : l'overlay reste affiché, l'utilisateur peut réessayer ou basculer sur le mot de passe.
        });
    }

    function validerMotDePasse() {
        var motDePasse = document.getElementById('verrou-mdp-input').value;
        var erreurEl = document.getElementById('verrou-mdp-erreur');
        var meta = document.querySelector('meta[name="csrf-token"]');

        fetch('/verrou-app/verifier-mot-de-passe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': meta ? meta.content : '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ 'mot-de-passe': motDePasse }),
        })
            .then(function (reponse) { return reponse.json(); })
            .then(function (donnees) {
                if (donnees.succes) {
                    retirerVerrou();
                } else {
                    erreurEl.textContent = donnees.erreur || 'Mot de passe incorrect.';
                }
            })
            .catch(function () {
                erreurEl.textContent = 'Erreur réseau, réessayez.';
            });
    }

    // Verrou dès l'ouverture de l'app, sauf si on est juste en train de naviguer entre
    // les pages d'une app déjà déverrouillée (voir appDejaDeverrouilleeCetteSession).
    if (!appDejaDeverrouilleeCetteSession()) {
        creerVerrou();
    }

    // ...et à chaque retour au premier plan après mise en arrière-plan
    // (les gardes ci-dessus empêchent que l'ouverture de la fenêtre biométrique
    // elle-même ne redéclenche le verrou juste après une réussite).
    if (App) {
        App.addListener('appStateChange', function (etat) {
            if (!etat.isActive) {
                return;
            }

            if (ignorerProchainResume && Date.now() - ignorerProchainResume < DELAI_IGNORER_RESUME_MS) {
                ignorerProchainResume = 0;
                return;
            }

            creerVerrou();
        });
    }
})();
