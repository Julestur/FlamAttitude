@extends('layouts.app')

@section('content')
<div class="page-dimension">
    <div class="contenu-principal">  
        <div class="contenu-centre">
            <br><br><br><br><br><br><br><br>
            <h4 class="texte_aff" style="font-size: xx-large;"> Inscription terminée !</h4>
            
            <p class="texte_aff">
                {{ htmlspecialchars($prenom) }} peut maintenant <span id="surlignage">se connecter</span>.
            </p>
            
            {{-- script de confettis --}}
            <script src="{{ asset('js/confetti.js') }}"></script>
            
            <div class="bouton-position">
                <p><a href="{{ route('accueil') }}"><input type="button" value="Retour" class="bouton-style"></a></p>
            </div>

            <br><br><br><br><br><br><br><br><br><br>
        </div>
    </div>
</div>
@endsection
