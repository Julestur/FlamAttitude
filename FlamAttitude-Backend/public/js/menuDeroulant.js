const profil = document.getElementById('profil');
const profilDeroulant = document.getElementById('profil-deroulant');
const overlay = document.getElementById('overlay');

profil.addEventListener('click', (evt) => {
    updatePositionSouris(evt);
    profil.classList.toggle('active');
    profilDeroulant.classList.toggle('active');
    overlay.classList.toggle('active');
    evt.stopPropagation();
});

profilDeroulant.addEventListener('click', (e) => {
    e.stopPropagation();
});

function updatePositionSouris(evt)
{
    document.documentElement.style.setProperty('--pos-souris-x', `${evt.clientX}px`);
    document.documentElement.style.setProperty('--pos-souris-y', `${evt.clientY}px`);
}

document.addEventListener('click', () => {
    profil.classList.remove('active');
    profilDeroulant.classList.remove('active');
    overlay.classList.remove('active');
});