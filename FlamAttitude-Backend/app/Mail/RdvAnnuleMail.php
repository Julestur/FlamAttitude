<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RdvAnnuleMail extends Mailable
{
    use Queueable, SerializesModels;

    public $rdv;

    public $pourMembre;

    public function __construct($rdv, bool $pourMembre = false)
    {
        $this->rdv = $rdv;
        $this->pourMembre = $pourMembre;
    }

    public function build()
    {
        return $this->subject("Annulation de votre rendez-vous Flam'Attitude")
            ->view('Mail.rdvAnnule');
    }
}
