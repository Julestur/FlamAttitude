/**
 * Récupère le jeton d'appareil flashé en session juste après une connexion complète
 * réussie (mdp + code email) et le stocke localement sur le téléphone, pour permettre
 * les prochaines ouvertures de l'app par Face ID/empreinte uniquement (voir login.blade.php).
 */
(function () {
    if (typeof Capacitor === 'undefined' || !Capacitor.isNativePlatform()) {
        return;
    }

    var jeton = window.FLAMATTITUDE_NOUVEAU_JETON_APPAREIL;

    if (!jeton) {
        return;
    }

    var Preferences = Capacitor.Plugins.Preferences;

    if (!Preferences) {
        return;
    }

    Preferences.set({ key: 'jetonAppareilFlamAttitude', value: jeton });
})();
