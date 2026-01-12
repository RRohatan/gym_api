<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BienvenidaMiembroMail extends Mailable
{
    use Queueable, SerializesModels;

    public $member;
    public $gym;

    // Recibimos los datos del cliente y de SU gimnasio
    public function __construct($member, $gym)
    {
        $this->member = $member;
        $this->gym = $gym;
    }

    public function build()
    {
        return $this->subject('🎉 ¡Bienvenido a ' . $this->gym->nombre . '! - Tu acceso está listo')
                    ->view('emails.bienvenida_miembro');
    }
}
