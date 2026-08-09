/**
 * Navigation semaine par semaine dans une grille EDT : recharge uniquement
 * le conteneur de la grille via fetch, sans recharger la page.
 */
function edtChargerSemaine(url, conteneurId) {
    const conteneur = document.getElementById(conteneurId);
    if (!conteneur) return;

    conteneur.style.opacity = '0.5';

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (reponse) {
            if (!reponse.ok) throw new Error('Erreur réseau');
            return reponse.text();
        })
        .then(function (html) {
            conteneur.outerHTML = html;
        })
        .catch(function (erreur) {
            console.error('Erreur EDT :', erreur);
            conteneur.style.opacity = '1';
        });
}
