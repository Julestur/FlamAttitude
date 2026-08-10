<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RdvDeplaceMail extends Mailable
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
        return $this->subject("Modification de votre rendez-vous Flam'Attitude")
            ->view('Mail.rdvDeplace');
    }
}
