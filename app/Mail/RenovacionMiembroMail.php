<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RenovacionMiembroMail extends Mailable
{
    use Queueable, SerializesModels;

    public $member;
    public $gym;
    public $payment; // Recibimos el pago para mostrar el monto

    public function __construct($member, $gym, $payment)
    {
        $this->member = $member;
        $this->gym = $gym;
        $this->payment = $payment;
    }

    public function build()
    {
        $nombreGym = $this->gym->nombre;

        return $this->from(env('MAIL_FROM_ADDRESS'), $nombreGym)
                    ->subject('✅ Pago recibido - ¡Gracias por renovar en ' . $nombreGym . '!')
                    ->view('emails.renovacion_miembro');
    }
}
