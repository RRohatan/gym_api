<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReporteAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public $adminName;
    public $gymName;
    public $proximos;
    public $vencidos;

    public function __construct($adminName, $gymName, $proximos, $vencidos)
    {
        $this->adminName = $adminName;
        $this->gymName = $gymName;
        $this->proximos = $proximos;
        $this->vencidos = $vencidos;
    }

    public function build()
    {
        return $this->subject('📊 Reporte Diario de Vencimientos')
                    ->view('emails.reporte_admin'); // Apunta al archivo del Paso 1
    }
}
