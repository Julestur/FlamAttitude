<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RdvRappelMail extends Mailable
{
    use Queueable, SerializesModels;

    public $rdv;

    public $delaiLabel;

    public function __construct($rdv, string $delaiLabel)
    {
        $this->rdv = $rdv;
        $this->delaiLabel = $delaiLabel;
    }

    public function build()
    {
        return $this->subject("Rappel de rendez-vous {$this->delaiLabel} - Flam'Attitude")
                    ->view('Mail.rdvRappel');
    }
}
