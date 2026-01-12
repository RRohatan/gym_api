<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AvisoClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $member;
    public $gymName;
    public $fechaVencimiento;

    public function __construct($member, $gymName, $fechaVencimiento)
    {
        $this->member = $member;
        $this->gymName = $gymName;
        $this->fechaVencimiento = $fechaVencimiento;
    }

    public function build()
    {
        return $this->subject('💪 Te queremos seguir viendo en - ' . $this->gymName)
                    ->view('emails.aviso_cliente'); // Apunta al archivo del Paso 1
    }
}
