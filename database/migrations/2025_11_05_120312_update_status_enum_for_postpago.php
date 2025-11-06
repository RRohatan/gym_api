<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       // 1. Modificar la columna 'status' en 'memberships' para la nueva lógica
        // Usamos DB::statement para modificar un ENUM existente de forma segura
        // Añadimos 'inactive_unpaid' y 'pending'
        // Tu migración 2025_05_22_150305_create_memberships_table usa 'active', 'expired', 'cancelled'
        // Tu migración 2025_07_22_025423_add_outstanding_balance_to_memberships_table añade 'pending'
        // Esta migración los unifica y añade el nuevo estado clave 'inactive_unpaid'

        // Primero, asegurémonos que 'pending' exista por si la migración 2025_07_22... falló
        try {
            // Corregido: Se quitó el parámetro nombrado 'query:'
            DB::statement("ALTER TABLE memberships CHANGE COLUMN status status ENUM('active', 'expired', 'cancelled', 'pending') NOT NULL DEFAULT 'pending'");
        } catch (\Exception $e) {
            // Ignorar si falla (probablemente ya existe o es string)
        }

        // Ahora, añadimos el nuevo estado
        // Corregido: Se quitó el parámetro nombrado 'query:'
        DB::statement("ALTER TABLE memberships CHANGE COLUMN status status ENUM('active', 'expired', 'cancelled', 'pending', 'inactive_unpaid') NOT NULL DEFAULT 'pending'");;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         // Revertir el ENUM a como estaba antes de esta migración
        DB::statement("ALTER TABLE memberships CHANGE COLUMN status status ENUM('active', 'expired', 'cancelled', 'pending') NOT NULL DEFAULT 'pending'");
    }
};
