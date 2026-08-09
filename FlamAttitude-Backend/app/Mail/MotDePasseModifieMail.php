<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MotDePasseModifieMail extends Mailable
{
    use Queueable, SerializesModels;

    public function build()
    {
        return $this->subject("Votre mot de passe Flam'Attitude a été modifié")
                    ->view('Mail.motDePasseModifie');
    }
}
