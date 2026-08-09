<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modèle Eloquent sur la table `utilisateur`, existant uniquement pour permettre
 * l'émission de jetons Sanctum (HasApiTokens) côté API. Le reste de l'application
 * continue de lire/écrire cette table via des requêtes DB::table brutes.
 */
class Utilisateur extends Model
{
    use HasApiTokens;

    protected $table = 'utilisateur';

    protected $primaryKey = 'idUtilisateur';
}
