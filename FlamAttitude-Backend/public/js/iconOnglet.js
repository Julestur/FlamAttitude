


    // On attend que tout soit chargé
    window.addEventListener('DOMContentLoaded', () => {
        const favicon = document.getElementById('iconOnglet');
        
   
        const lightIcon = window.laravelAssets.lightIcon;
        const darkIcon = window.laravelAssets.darkIcon;

        const updateFavicon = () => {
            const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const newHref = isDark ? darkIcon : lightIcon;
            
            // On ajoute ?t=... pour forcer le navigateur à recharger l'image
            favicon.href = newHref + "?t=" + new Date().getTime();
        };

        // Écouteur de changement de mode
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateFavicon);
        
        // Premier lancement
        updateFavicon();
    });