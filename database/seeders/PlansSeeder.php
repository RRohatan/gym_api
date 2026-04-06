<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LucasDotVin\Soulbscription\Models\Feature;
use LucasDotVin\Soulbscription\Models\Plan;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        // ── Features (módulos del sistema) ──────────────────────────────
        $features = [
            ['name' => 'members',            'display' => 'Clientes'],
            ['name' => 'memberships',        'display' => 'Membresías'],
            ['name' => 'payments',           'display' => 'Pagos'],
            ['name' => 'supplement-pos',     'display' => 'Punto de Venta'],
            ['name' => 'inventory',          'display' => 'Inventario'],
            ['name' => 'cashbox',            'display' => 'Caja'],
            ['name' => 'statistics',         'display' => 'Estadísticas'],
            ['name' => 'fingerprint-access', 'display' => 'Acceso biométrico'],
            ['name' => 'email-alerts',       'display' => 'Alertas por correo'],
            ['name' => 'qr-register',        'display' => 'Registro QR'],
        ];

        $createdFeatures = [];
        foreach ($features as $f) {
            $createdFeatures[$f['name']] = Feature::firstOrCreate(
                ['name' => $f['name']],
                ['consumable' => false, 'quota' => false],
            );
        }

        // ── Plan Promo Mensual ───────────────────────────────────────────
        $mensual = Plan::firstOrCreate(
            ['name' => 'Plan Promo Mensual'],
            [
                'price'            => 60000,
                'periodicity'      => 1,
                'periodicity_type' => 'month',
                'grace_days'       => 3,
            ],
        );

        foreach ($createdFeatures as $feature) {
            if (! $mensual->features()->where('feature_id', $feature->id)->exists()) {
                $mensual->features()->attach($feature->id);
            }
        }

        // ── Plan Promo Anual ─────────────────────────────────────────────
        $anual = Plan::firstOrCreate(
            ['name' => 'Plan Promo Anual'],
            [
                'price'            => 720000,
                'periodicity'      => 1,
                'periodicity_type' => 'year',
                'grace_days'       => 5,
            ],
        );

        foreach ($createdFeatures as $feature) {
            if (! $anual->features()->where('feature_id', $feature->id)->exists()) {
                $anual->features()->attach($feature->id);
            }
        }

        $this->command->info('✓ Planes y features creados: Plan Promo Mensual ($60.000) y Plan Promo Anual ($720.000)');
    }
}
