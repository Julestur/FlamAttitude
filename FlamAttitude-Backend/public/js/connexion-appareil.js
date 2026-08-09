/**
 * Sur la page de connexion, depuis l'app mobile : si un jeton d'appareil de confiance
 * est stocké localement (staff/admin déjà passés par une connexion complète il y a
 * moins de 30 jours), on tente une reconnexion silencieuse par Face ID/empreinte au
 * lieu d'afficher le formulaire mot de passe + code email.
 */
(function () {
    if (typeof Capacitor === 'undefined' || !Capacitor.isNativePlatform()) {
        return;
    }

    var Preferences = Capacitor.Plugins.Preferences;
    var NativeBiometric = Capacitor.Plugins.NativeBiometric;

    if (!Preferences || !NativeBiometric) {
        return;
    }

    var CLE_JETON = 'jetonAppareilFlamAttitude';
    var overlay = document.getElementById('attenteConnexionAppareil');

    if (new URLSearchParams(window.location.search).get('jetonAppareilRevoque') === '1') {
        // Déconnexion volontaire : on efface le jeton local, pas de reconnexion silencieuse.
        Preferences.remove({ key: CLE_JETON });
        return;
    }

    Preferences.get({ key: CLE_JETON }).then(function (resultat) {
        var jeton = resultat && resultat.value;

        if (!jeton) {
            return;
        }

        if (overlay) {
            overlay.style.display = 'flex';
        }

        NativeBiometric.verifyIdentity({
            reason: "Se connecter à Flam'Attitude",
            title: 'Authentification requise',
        }).then(function () {
            return envoyerJeton(jeton);
        }).catch(function () {
            // Biométrie annulée/échouée : on revient au formulaire classique.
            if (overlay) {
                overlay.style.display = 'none';
            }
        });
    }).catch(function () {
        // Pas de jeton lisible : formulaire classique.
    });

    function envoyerJeton(jeton) {
        var meta = document.querySelector('meta[name="csrf-token"]');

        return fetch('/connexion-appareil', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': meta ? meta.content : '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ jeton: jeton }),
        })
            .then(function (reponse) { return reponse.json(); })
            .then(function (donnees) {
                if (donnees.succes && donnees.redirection) {
                    // Évite que le verrou local de l'app (verrou-app.js) ne redemande
                    // aussitôt une biométrie juste après cette reconnexion réussie.
                    try {
                        sessionStorage.setItem('flamattitudeAppDeverrouillee', '1');
                    } catch (e) { /* stockage indisponible, tant pis */ }

                    window.location.href = donnees.redirection;
                } else if (overlay) {
                    // Jeton refusé par le serveur (expiré/révoqué) : formulaire classique.
                    Preferences.remove({ key: CLE_JETON });
                    overlay.style.display = 'none';
                }
            })
            .catch(function () {
                if (overlay) {
                    overlay.style.display = 'none';
                }
            });
    }
})();
