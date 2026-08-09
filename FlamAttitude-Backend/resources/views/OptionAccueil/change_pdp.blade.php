@extends('layouts.app')

@section('content')
<div class="page-dimension"> 
    <div class="contenu-principal">  
        <div class="accueil">  
            <h2 class="titre1">Changement de photo de profil</h2>
        </div>

        <hr id="redline-mdp">

        @if(session('confirmation'))
            <div style="color: #21a13f; background-color: #d4edda; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: bold; border: 1px solid #c3e6cb;">
                {{ session('confirmation') }}
            </div>
        @endif
        @if(session('erreur'))
            <div style="color: #fa021b; background-color: #f8d7da; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 14px;">
                {{ session('erreur') }}
            </div>
        @endif
        @if ($errors->any())
            <div style="color: #fa021b; background-color: #f8d7da; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 14px;">
                <ul style="margin: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('changementPDP_GESTION') }}" method="POST" enctype="multipart/form-data" class="portail">
            @csrf


            <div id="zone_DragAndDrop"> <ion-icon name="image-outline" id="icon-file-drop"></ion-icon>
                <input type="file" name="photo_profil" id="input-file" hidden required accept=".png,.jpg,.jpeg">
            </div>

            <div id="info-drop-zone" style="margin-top: 10px; color: #555;"></div>



            <p class="info">Veuillez déposer une photo au format .png ou .jpg</p>
            
            <div class="bouton">
                <input type="submit" value="Enregistrer" id="bouton-style-connexion" disabled>
            </div>
        </form>
    </div>
</div>

<br><br>
<br>
<br>
<br>
<br>


<script>

    // GESTION DU DRAG AND DROP
    let inputFile = document.getElementById('input-file');
    let dropZone = document.getElementById('zone_DragAndDrop');
    let icon = document.getElementById('icon-file-drop');
    let infoDropzone = document.getElementById('info-drop-zone');
    let boutonEnvoi = document.getElementById('bouton-style-connexion');

    // Une photo prise directement avec l'appareil photo (pleine résolution + EXIF +
    // profil colorimétrique) pèse souvent 3 à 8 Mo, contre quelques centaines de Ko pour
    // une capture d'écran : elle dépassait silencieusement la limite d'envoi côté serveur.
    // On la redimensionne et recompresse ici, côté téléphone, avant l'envoi.
    const DIMENSION_MAX = 1600;
    const QUALITE_JPEG = 0.85;

    function chargerImageClassique(file) {
        return new Promise((resolve) => {
            const image = new Image();
            const urlTemporaire = URL.createObjectURL(file);
            image.onload = () => { URL.revokeObjectURL(urlTemporaire); resolve(image); };
            image.onerror = () => { URL.revokeObjectURL(urlTemporaire); resolve(null); };
            image.src = urlTemporaire;
        });
    }

    async function redimensionner(file) {
        let source = null;

        // createImageBitmap decode/redimensionne de façon bien plus rapide que <img> + canvas
        // pour les très grandes photos (12 Mpx et plus) : c'est ce qui rendait le traitement
        // très long pour certaines photos prises à l'appareil.
        if ('createImageBitmap' in window) {
            try {
                source = await createImageBitmap(file);
            } catch (e) {
                source = null;
            }
        }

        if (!source) {
            source = await chargerImageClassique(file);
        }

        if (!source) {
            return file;
        }

        let largeur = source.width || source.naturalWidth;
        let hauteur = source.height || source.naturalHeight;

        if (largeur > DIMENSION_MAX || hauteur > DIMENSION_MAX) {
            const ratio = Math.min(DIMENSION_MAX / largeur, DIMENSION_MAX / hauteur);
            largeur = Math.round(largeur * ratio);
            hauteur = Math.round(hauteur * ratio);
        }

        const canvas = document.createElement('canvas');
        canvas.width = largeur;
        canvas.height = hauteur;
        canvas.getContext('2d').drawImage(source, 0, 0, largeur, hauteur);

        if (source.close) {
            source.close();
        }

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', QUALITE_JPEG));

        if (!blob) {
            return file;
        }

        const nomFinal = file.name.replace(/\.(png|jpe?g)$/i, '') + '.jpg';
        return new File([blob], nomFinal, { type: 'image/jpeg' });
    }

    async function gererFichier(files) {
        if (files.length > 0) {
            let file = files[0];
            let ext = file.name.split('.').pop().toLowerCase();

            if (!['png', 'jpg', 'jpeg'].includes(ext)) {
                icon.classList.add('error-shake');
                setTimeout(() => icon.classList.remove('error-shake'), 300);
                return;
            }

            infoDropzone.innerHTML = '<span class="spinner-pdp"></span> Traitement de la photo…';
            boutonEnvoi.disabled = true;

            const fichierPret = await redimensionner(file);

            infoDropzone.innerHTML = fichierPret.name;
            icon.name = "checkmark-outline";
            boutonEnvoi.disabled = false;
            boutonEnvoi.classList.add('enabled');

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(fichierPret);
            inputFile.files = dataTransfer.files;
        }
    }

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-hover');
    });

    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-hover'));

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-hover');
        gererFichier(e.dataTransfer.files);
    });

    dropZone.addEventListener('click', () => inputFile.click());
    inputFile.addEventListener('change', (e) => gererFichier(e.target.files));
</script>
@endsection