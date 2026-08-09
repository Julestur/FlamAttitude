@extends('layouts.app')

@section('content')



<div class="page-dimension">
    <div class="contenu-principal"> 
            
        <!-- AFFICHAGE DE LA BARRE DE CHOIX AVEC LES BOUTTONS -->
        <div class="accueil">  
            <h2 class="titre1">Confirmation de suppression</h2>
        </div>
        <hr id="redline-mdp">
        <br><br>

        <!-- AFFICHAGE DU MESSAGE ET DES BOUTTONS DE CONFIRMATION -->
        <div class="boiteAlerte" style="text-align: center; margin-top: 50px;">
            <div style="border: 2px solid #e74c3c; padding: 30px; display: inline-block; border-radius: 10px; background-color: #fff5f5;">
                
                <h2 style="color: #e74c3c; margin-top: 0;font-family: var(--font-family-titre);">
                    <ion-icon name="warning-outline"></ion-icon> Confirmation de suppression
                </h2>
                
                <h3 style="font-family: var(--font-family-titre);">
                    Êtes-vous sûr de vouloir supprimer définitivement :<br>
                    <strong style="font-family: var(--font-family-titre);color: #2c3e50;">{{ $nomAffichage }}</strong> ?
                </h3>

                <p style="color: #7f8c8d;">
                    <strong>Attention : cette action est définitive.</strong>
                </p>

                <br>

                <form action="{{ route('suppressionAdmin.Etape1_GESTION') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" value="{{ $id }}">

                    <div style="display: flex; gap: 20px; justify-content: center;">

                        <button type="button"  class="annulerSuppr" onclick="window.location.href='{{ route('suppressionAdmin.Etape1_VU') }}'" >
                            Annuler
                        </button>

                        <button type="submit" class="confirmerSuppr">
                            Confirmer
                        </button>
                    </div>
                </form>

            </div>
        </div>


    </div>
</div>
        <br><br><br><br><br><br>

@endsection