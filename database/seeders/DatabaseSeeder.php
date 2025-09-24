<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Gimnasio;
use App\Models\Member;
use App\Models\MembershipType;
use App\Models\MembershipPlan;
use App\Models\Membership;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $gimnasio = Gimnasio::factory()->create();
        // Crear un usuario de prueba
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'ella@gmail.com',
            'gimnasio_id' => $gimnasio->id, // 👈 Esto es lo que falta
        ]);

        // Crear 3 gimnasios
        Gimnasio::factory(3)->create()->each(function ($gimnasio) {
            // Marcar aleatoriamente algunos gimnasios con control de acceso habilitado
            $gimnasio->uses_access_control = (bool) random_int(0, 1);
            $gimnasio->save();
            // Crear 2 tipos de membresía por gimnasio
            $types = MembershipType::factory(2)->create();

            // Por cada tipo, crear 1-2 planes
            $types->each(function ($type) use ($gimnasio) {
                MembershipPlan::factory(rand(1, 2))->create([
                    'membership_type_id' => $type->id,
                    'gym_id' => $gimnasio->id,
                ]);
            });

            // Crear 5 miembros por gimnasio
            Member::factory(5)->create([
                'gimnasio_id' => $gimnasio->id,
            ])->each(function ($member) use ($gimnasio) {
                // Si el gimnasio usa control de acceso, asignar identificación y huella obligatorias
                if ($gimnasio->uses_access_control) {
                    $member->identification = 'ID-' . strtoupper(uniqid());
                    $member->fingerprint_data = base64_encode(random_bytes(16));
                    $member->save();
                }
                // Seleccionar un plan al azar
                $plan = MembershipPlan::where('gym_id', $gimnasio->id)->inRandomOrder()->first();
                $inicio = now();
                $duracion = $plan->duracion_dias ?? 30;

                Membership::create([
                    'member_id' => $member->id,
                    'plan_id' => $plan->id,
                    'start_date' => $inicio,
                    'end_date' => $inicio->copy()->addDays($duracion),
                ]);
            });
        });
    }
}
