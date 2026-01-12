<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Membership;
use App\Models\User;
use App\Models\Gimnasio;
use Illuminate\Support\Facades\Mail;
use App\Mail\AvisoClienteMail;
use App\Mail\ReporteAdminMail;
use Carbon\Carbon;

class EnviarAlertasGym extends Command
{
    protected $signature = 'gym:alertas';
    protected $description = 'Envía avisos a clientes y reporte diario a admins';

    public function handle()
    {
        $this->info('🚀 Iniciando proceso de alertas...');

        $hoy = Carbon::now();
        $fechaEn3Dias = $hoy->copy()->addDays(3);

        // 1. Buscamos TODOS los usuarios que sean administradores de un gym
        $admins = User::whereNotNull('gimnasio_id')->get();

        foreach ($admins as $admin) {

            if (!$admin->email) continue; // Si el admin no tiene correo, saltamos

            $gymId = $admin->gimnasio_id;
            // Obtenemos el nombre del gym
            $gymName = Gimnasio::find($gymId)->nombre ?? 'Gimnasio';

            $this->info("🔄 Procesando Admin: {$admin->name} (Gym ID: $gymId)");

            // --- A. BUSCAR PRÓXIMOS A VENCER (Para avisar al CLIENTE) ---
            $proximosAVencer = Membership::whereHas('member', function($q) use ($gymId) {
                    $q->where('gimnasio_id', $gymId);
                })
                ->where('status', 'active')
                ->whereDate('end_date', $fechaEn3Dias->format('Y-m-d'))
                ->with('member')
                ->get();

            // Enviamos correo individual a cada cliente
            foreach ($proximosAVencer as $m) {
                if ($m->member->email) {
                    Mail::to($m->member->email)
                        ->send(new AvisoClienteMail($m->member, $gymName, $m->end_date));
                    $this->info("   -> Aviso enviado a cliente: " . $m->member->name);
                }
            }

            // --- B. BUSCAR LOS QUE VENCIERON HOY (Para el reporte ADMIN) ---
            $vencidosHoy = Membership::whereHas('member', function($q) use ($gymId) {
                    $q->where('gimnasio_id', $gymId);
                })
                ->where('status', 'active') // Aún figuran activos pero la fecha ya pasó
                ->whereDate('end_date', '<=', $hoy->format('Y-m-d'))
                ->with(['member', 'plan'])
                ->get();

            // Actualizamos estado a 'expired' y generamos deuda
            foreach ($vencidosHoy as $m) {
                $m->status = 'expired';
                if ($m->outstanding_balance == 0 && $m->plan) {
                    $m->outstanding_balance = $m->plan->price;
                }
                $m->save();
            }

            // --- C. ENVIAR REPORTE AL ADMIN ---
            // Solo enviamos correo al admin si hay algo que reportar
            if ($proximosAVencer->count() > 0 || $vencidosHoy->count() > 0) {

                Mail::to($admin->email)
                    ->send(new ReporteAdminMail($admin->name, $gymName, $proximosAVencer, $vencidosHoy));

                $this->info("   -> 📊 Reporte enviado al Admin: " . $admin->email);
            } else {
                $this->info("   -> Nada que reportar hoy para este admin.");
            }
        }

        $this->info('✅ Proceso finalizado correctamente.');
    }
}
