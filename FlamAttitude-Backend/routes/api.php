<?php

use App\Http\Controllers\Api\AccueilController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CompteController;
use App\Http\Controllers\Api\DisponibiliteController;
use App\Http\Controllers\Api\EspaceClientController;
use App\Http\Controllers\Api\InscriptionController;
use App\Http\Controllers\Api\MotDePasseOublieController;
use App\Http\Controllers\Api\PlanningAdminController;
use App\Http\Controllers\Api\TypeRdvController;
use App\Http\Controllers\Api\UtilisateurController;
use Illuminate\Support\Facades\Route;

// API JSON consommée par l'app Flutter (auth par jeton Sanctum, sans session/cookie).
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verifier-code', [AuthController::class, 'verifierCode']);
    Route::post('/renvoyer-code', [AuthController::class, 'renvoyerCode']);
    Route::post('/appareil/connecter', [AuthController::class, 'connecterAppareil']);
    Route::post('/mot-de-passe-oublie', [MotDePasseOublieController::class, 'envoyer']);

    Route::post('/inscription', [InscriptionController::class, 'creer']);
    Route::post('/inscription/verifier-code', [InscriptionController::class, 'verifierCode']);
    Route::post('/inscription/renvoyer-code', [InscriptionController::class, 'renvoyerCode']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/moi', [AuthController::class, 'moi']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/accueil', [AccueilController::class, 'index']);
    Route::post('/compte/changer-mot-de-passe', [CompteController::class, 'changerMotDePasse']);
    Route::post('/compte/changer-photo-profil', [CompteController::class, 'changerPhotoProfil']);
    Route::post('/compte/jeton-fcm', [CompteController::class, 'enregistrerJetonFcm']);

    Route::middleware('role.api:staff')->group(function () {
        Route::get('/clients', [ClientController::class, 'rechercher']);
        Route::get('/clients/{id}', [ClientController::class, 'dossier']);
        Route::post('/clients/{id}/contact', [ClientController::class, 'modifierContact']);
        Route::post('/clients/{id}/documents', [ClientController::class, 'ajouterDocument']);
        Route::post('/clients/{id}/factures', [ClientController::class, 'ajouterFacture']);

        Route::get('/mon-edt/grille', [DisponibiliteController::class, 'grille']);
        Route::post('/mon-edt/toggle', [DisponibiliteController::class, 'toggle']);
        Route::get('/mon-edt/rdv', [DisponibiliteController::class, 'monEdt']);
        Route::post('/mon-edt/rdv/{id}/annuler', [DisponibiliteController::class, 'annulerRdv']);
    });

    Route::middleware('role.api:client')->group(function () {
        Route::get('/mon-espace', [EspaceClientController::class, 'accueil']);
        Route::get('/mon-espace/creneaux', [EspaceClientController::class, 'creneaux']);
        Route::get('/mon-espace/creneaux/grille', [EspaceClientController::class, 'grille']);
        Route::post('/mon-espace/creneaux/reserver', [EspaceClientController::class, 'reserver']);
        Route::post('/mon-espace/creneaux/{id}/annuler', [EspaceClientController::class, 'annuler']);
    });

    Route::middleware('role.api:admin')->group(function () {
        Route::post('/utilisateurs', [UtilisateurController::class, 'creer']);
        Route::get('/utilisateurs/supprimables', [UtilisateurController::class, 'listerPourSuppression']);
        Route::delete('/utilisateurs/{id}', [UtilisateurController::class, 'supprimer']);

        Route::get('/types-rdv', [TypeRdvController::class, 'index']);
        Route::post('/types-rdv', [TypeRdvController::class, 'creer']);
        Route::post('/types-rdv/{id}', [TypeRdvController::class, 'modifier']);
        Route::post('/types-rdv/{id}/basculer', [TypeRdvController::class, 'basculerActif']);
        Route::post('/types-rdv-affectations', [TypeRdvController::class, 'enregistrerAffectations']);

        Route::get('/admin/membres', [PlanningAdminController::class, 'membres']);
        Route::get('/admin/membres/{id}/grille', [PlanningAdminController::class, 'grilleMembre']);
        Route::post('/admin/rdv/{id}/deplacer', [PlanningAdminController::class, 'deplacerRdv']);
        Route::post('/admin/rdv/{id}/annuler', [PlanningAdminController::class, 'annulerRdv']);
    });
});
