<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class ReinitialisationMDP extends Mailable
{
    use Queueable, SerializesModels;

    public $lien;

    public function __construct($lien) {
        
        $this->lien = $lien; // lien de reinitialisation

    }

    public function build() {
        
        return $this->subject('Réinitialisation de votre mot de passe') //Sujet du mail
                    ->view('Mail.reinitialisation_MDP'); //Coprs du mail
    }
}