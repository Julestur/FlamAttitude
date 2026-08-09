<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoubleAuthController;
use App\Http\Controllers\accueilController;


use App\Http\Controllers\OPTION_changementMDPController;
use App\Http\Controllers\OPTION_changementPDPController;
use App\Http\Controllers\OPTION_ajoutUtilisateurController;
use App\Http\Controllers\OPTION_suppressionUtilisateurController;
use App\Http\Controllers\MDP_OublieController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\StaffClientController;
use App\Http\Controllers\DisponibiliteController;
use App\Http\Controllers\TypeRdvController;
use App\Http\Controllers\VerrouAppController;
use App\Http\Controllers\ConnexionAppareilController;


// --------------------------------------------------------------------------------------------------------------------------------------
// ------------------------------ GESTION DES CONNEXIONS ET DECONNEXION -----------------------------------------------------------------
// --------------------------------------------------------------------------------------------------------------------------------------



// Lien vers page de connexion
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');

// Traitement des données du formulaire
Route::post('/login-process', [AuthController::class, 'login'])->name('login.post');

// Processus de déconnexion
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Reconnexion silencieuse depuis l'app mobile via jeton d'appareil + biométrie (staff/admin)
Route::post('/connexion-appareil', [ConnexionAppareilController::class, 'connecter'])->name('connexionAppareil');




// --------------------------------------------------------------------------------------------------------------------------------------
// ------------------------------ DOUBLE AUTHENTIFICATION (CODE PAR EMAIL) --------------------------------------------------------------
// --------------------------------------------------------------------------------------------------------------------------------------

Route::get('/verification-code', [DoubleAuthController::class, 'afficherFormulaire'])->name('doubleAuth.afficher');
Route::post('/verification-code', [DoubleAuthController::class, 'verifier'])->name('doubleAuth.verifier');
Route::post('/verification-code/renvoyer', [DoubleAuthController::class, 'renvoyer'])->name('doubleAuth.renvoyer');




// --------------------------------------------------------------------------------------------------------------------------------------
// ------------------------------ GESTION DES INSCRIPTIONS DEPUIS LE PORTAIL ------------------------------------------------------------
// --------------------------------------------------------------------------------------------------------------------------------------


use App\Http\Controllers\InscriptionController;


// Étape 1 : identité (nom, prénom, statut)
Route::get('/inscription', [InscriptionController::class, 'Vu_Inscription_Etape1'])->name('inscription.Etape1_VU');
Route::post('/inscription', [InscriptionController::class, 'Traitement_Inscription_Etape1'])->name('inscription.Etape1_GESTION');

// Étape 2 : identifiant et email
Route::get('/inscription/identifiant', [InscriptionController::class, 'Vu_Inscription_Etape2'])->name('inscription.Etape2_VU');
Route::post('/inscription/identifiant', [InscriptionController::class, 'Traitement_Inscription_Etape2'])->name('inscription.Etape2_GESTION');

// Étape 3 : mot de passe
Route::get('/inscription/mot-de-passe', [InscriptionController::class, 'Vu_Inscription_Etape3'])->name('inscription.Etape3_VU');
Route::post('/inscription/mot-de-passe', [InscriptionController::class, 'Traitement_Inscription_Etape3'])->name('inscription.Etape3_GESTION');

// Étape 4 : vérification du code recu par mail
Route::get('/inscription/verification', [InscriptionController::class, 'Vu_Inscription_Etape4'])->name('inscription.Etape4_VU');
Route::post('/inscription/verification', [InscriptionController::class, 'Traitement_Inscription_Etape4'])->name('inscription.Etape4_GESTION');

// Étape 5 : si code bon, affichage de la page de succès
Route::get('/inscription/succes', [InscriptionController::class, 'Vu_Inscription_Etape5'])->name('inscription.Etape5_VU');









// --------------------------------------------------------------------------------------------------------------------------------------
// ------------------------------ REINITIALISATION MDP DEPUIS PORTAIL -------------------------------------------------------------------
// --------------------------------------------------------------------------------------------------------------------------------------


    // Page de saisie de l'email
    Route::get('/mot-de-passe-oublie', function () {
        return view('gestionMDP.MDP_Oublie'); 
    })->name('password.request');

    // Traitement de l'envoi du mail
    Route::post('/mot-de-passe-oublie', [MDP_OublieController::class, 'sendResetLink'])
        ->name('password.email.process');

    // Affichage du formulaire 
    Route::get('/reinitialisation-mot-de-passe/{token}', [MDP_OublieController::class, 'showResetForm'])
        ->name('password.reset');

    // Validation du nouveau mot de passe
    Route::post('/Réinitialisation-mot-de-passe', [MDP_OublieController::class, 'updatePassword'])
        ->name('reinitMDP_GESTION');










Route::middleware(['check.connexion'])->group(function () {

    // Redirection vers la page d'accueil (contenu adapté au rôle dans le contrôleur)
    Route::get('/accueil', [accueilController::class, 'index'])->name('accueil');

    // Déverrouillage local de l'app mobile (verrou biométrique/mdp à chaque ouverture, staff/admin)
    Route::post('/verrou-app/verifier-mot-de-passe', [VerrouAppController::class, 'verifierMotDePasse'])->name('verrouApp.verifierMdp');

    // --------------------------------------------------------------------------------------------------------------------------------------
    // ---------------------------- FONCTIONNALITÉS COMMUNES À TOUS LES RÔLES ---------------------------------------------------------------
    // --------------------------------------------------------------------------------------------------------------------------------------

    // GESTION DU CHANGEMENT DE MDP
    Route::post('/changement-mot-de-passe', [OPTION_changementMDPController::class, 'changementMDP'])
        ->name('changementMDP_GESTION');
    Route::get('/changement-mot-de-passe', [OPTION_changementMDPController::class, 'affichageChangement'])->name('aff.changementMDP');

    // GESTION DU CHANGEMENT DE PHOTO DE PROFIL
    Route::get('/changement-photo-de-profil', [OPTION_changementPDPController::class, 'affichageChangementPDP'])->name('aff.changementPDP');
    Route::post('/changement-photo-de-profil', [OPTION_changementPDPController::class, 'changementPDP'])
        ->name('changementPDP_GESTION');


    // --------------------------------------------------------------------------------------------------------------------------------------
    // ---------------------------- ESPACE CLIENT (rôle client uniquement) ------------------------------------------------------------------
    // --------------------------------------------------------------------------------------------------------------------------------------

    Route::middleware(['role:client'])->prefix('mon-espace')->name('mon-espace.')->group(function () {
        Route::get('/', [ClientController::class, 'accueil'])->name('accueil');
        Route::get('/creneaux', [ClientController::class, 'creneaux'])->name('creneaux');
        Route::get('/creneaux/grille', [ClientController::class, 'grille'])->name('creneaux.grille');
        Route::post('/creneaux/reserver', [ClientController::class, 'reserver'])->name('creneaux.reserver');
        Route::post('/creneaux/{id}/annuler', [ClientController::class, 'annuler'])->name('creneaux.annuler');
    });


    // --------------------------------------------------------------------------------------------------------------------------------------
    // ---------------------------- ESPACE STAFF (admin + membre de l'entreprise) -----------------------------------------------------------
    // --------------------------------------------------------------------------------------------------------------------------------------

    Route::middleware(['role:staff'])->group(function () {

        Route::get('/clients', [StaffClientController::class, 'rechercher'])->name('clients.recherche');
        Route::get('/clients/{id}', [StaffClientController::class, 'dossier'])->name('clients.dossier');
        Route::post('/clients/{id}/contact', [StaffClientController::class, 'modifierContact'])->name('clients.contact.modifier');
        Route::post('/clients/{id}/documents', [StaffClientController::class, 'ajouterDocument'])->name('clients.documents.ajouter');
        Route::post('/clients/{id}/factures', [StaffClientController::class, 'ajouterFacture'])->name('clients.factures.ajouter');

        Route::prefix('creneaux')->name('creneaux.')->group(function () {
            Route::get('/', [DisponibiliteController::class, 'index'])->name('gestion');
            Route::get('/mon-edt', [DisponibiliteController::class, 'monEdt'])->name('monEdt');
            Route::get('/grille', [DisponibiliteController::class, 'grille'])->name('grille');
            Route::post('/toggle', [DisponibiliteController::class, 'toggle'])->name('toggle');
            Route::post('/rdv/{id}/annuler', [DisponibiliteController::class, 'annulerRdv'])->name('rdv.annuler');
        });
    });


    // --------------------------------------------------------------------------------------------------------------------------------------
    // ---------------------------- GESTION DES COMPTES ET DES TYPES DE RDV (admin uniquement) ----------------------------------------------
    // --------------------------------------------------------------------------------------------------------------------------------------

    Route::middleware(['role:admin'])->group(function () {

        // GESTION DE L'AJOUT D'UTILISATEUR
        Route::get('/ajout-utilisateur', [OPTION_ajoutUtilisateurController::class, 'Vu_InscriptionAdmin_Etape1'])->name('inscriptionAdmin.Etape1_VU');
        Route::post('/ajout-utilisateur', [OPTION_ajoutUtilisateurController::class, 'Traitement_InscriptionAdmin_Etape1'])->name('inscriptionAdmin.Etape1_GESTION');
        Route::get('/ajout-utilisateur/confirmation', [OPTION_ajoutUtilisateurController::class, 'Vu_InscriptionAdmin_Etape2'])->name('inscriptionAdmin.Etape2_VU');

        // GESTION DE LA SUPPRESSION D'UTILISATEUR
        Route::get('/suppression-utilisateur', [OPTION_suppressionUtilisateurController::class, 'Vu_SuppressionAdmin_Etape1'])
            ->name('suppressionAdmin.Etape1_VU');
        Route::get('/suppression-utilisateur/confirmation', [OPTION_suppressionUtilisateurController::class, 'Vu_SuppressionAdmin_Etape2'])
            ->name('suppressionAdmin.Etape2_VU');
        Route::post('/suppression-utilisateur/traitement', [OPTION_suppressionUtilisateurController::class, 'Traitement_SuppressionAdmin_Etape1'])
            ->name('suppressionAdmin.Etape1_GESTION');

        // GESTION DES TYPES DE RDV
        Route::get('/types-rdv', [TypeRdvController::class, 'index'])->name('typesRdv.gestion');
        Route::post('/types-rdv', [TypeRdvController::class, 'creer'])->name('typesRdv.creer');
        Route::post('/types-rdv/{id}', [TypeRdvController::class, 'modifier'])->name('typesRdv.modifier');
        Route::post('/types-rdv/{id}/basculer', [TypeRdvController::class, 'basculerActif'])->name('typesRdv.basculerActif');
        Route::post('/types-rdv-affectations', [TypeRdvController::class, 'enregistrerAffectations'])->name('typesRdv.affectations');
    });

});








