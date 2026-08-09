<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class VerifCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;

    public function __construct($code) {
        $this->code = $code;
    }

    public function build() {
        return $this->subject('Confirmation de votre adresse e-mail') //Sujet du mail
                    ->view('Mail.verifMail'); //Coprs du mail
    }
}