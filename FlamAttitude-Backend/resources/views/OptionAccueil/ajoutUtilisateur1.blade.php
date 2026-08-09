@extends('layouts.app')

@section('content')

<div class="page-dimension">
    <div class="contenu-principal">
        <h2 class="titre1">Inscription d'un nouvel utilisateur</h2>
        <hr id="redline-mdp">

        <form action="{{ route('inscriptionAdmin.Etape1_GESTION') }}" method="POST" class="portail">

            @if ($errors->any())
                <div style="color: #fa021b; background-color: #f8d7da; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 14px;">
                    <ul style="margin: 0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @csrf

            <input type="text" name="nom" style="font-size:20px;" placeholder="Nom" class="contenu-portail" value="{{ old('nom') }}" required>
            <hr>
            <input type="text" name="prenom" style="font-size:20px;" placeholder="Prénom" class="contenu-portail" value="{{ old('prenom') }}" required>
            <hr>
            <input type="email" name="mail" style="font-size:20px;" placeholder="Email" class="contenu-portail" value="{{ old('mail') }}" required>
            <hr>
            <input type="text" name="pseudo" style="font-size:20px;" placeholder="Identifiant" class="contenu-portail" value="{{ old('pseudo') }}" required>
            <hr>
            <div class="champ-mdp">
                <input type="password" name="MDP" id="MDP" style="font-size:20px;" placeholder="Mot de passe" class="contenu-portail" required>
                <button type="button" class="toggle-mdp" data-cible="MDP" aria-label="Afficher le mot de passe"><ion-icon name="eye-outline"></ion-icon></button>
            </div>
            <hr>
            <div class="champ-mdp">
                <input type="password" name="MDP_confirmation" id="MDP_confirmation" style="font-size:20px;" placeholder="Confirmation" class="contenu-portail" required>
                <button type="button" class="toggle-mdp" data-cible="MDP_confirmation" aria-label="Afficher le mot de passe"><ion-icon name="eye-outline"></ion-icon></button>
            </div>
            <hr>

            <br><br>

            <label for="grade">Statut de l'utilisateur :</label>
            <select name="grade" id="grade-select" class="contenu-portail">
                <option value="" disabled {{ old('grade') === null ? 'selected' : '' }}> Choisissez le statut </option>
                <option value="Client" {{ old('grade') == 'Client' ? 'selected' : '' }}>Client</option>
                <option value="Membre_Entreprise" {{ old('grade') == 'Membre_Entreprise' ? 'selected' : '' }}>Membre de l'entreprise</option>
                <option value="Admin" {{ old('grade') == 'Admin' ? 'selected' : '' }}>Administrateur</option>
            </select>

            <div class="bouton">
                <input type="submit" value="Créer l'utilisateur" id="bouton-style-connexion">
            </div>
        </form>
        <br><br>
        <br><br>
        <br><br>

    </div>
</div>
@endsection
