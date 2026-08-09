document.querySelectorAll('.toggle-mdp').forEach(function (bouton) {
    bouton.addEventListener('click', function () {
        var champ = document.getElementById(bouton.dataset.cible);
        var icone = bouton.querySelector('ion-icon');
        var visible = champ.type === 'text';

        champ.type = visible ? 'password' : 'text';
        if (icone) {
            icone.setAttribute('name', visible ? 'eye-outline' : 'eye-off-outline');
        }
        bouton.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
    });
});
