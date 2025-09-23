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
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
                    // Relación con el miembro
            $table->foreignId('member_id')->constrained()->onDelete('cascade');

            // Método de acceso: 'cedula' o 'huella'
            $table->enum('method', ['cedula', 'huella']);

            // Estado del acceso: permitido o denegado
            $table->enum('status', ['permitido', 'denegado']);

            // Opcional: hora de entrada/salida
            $table->timestamp('accessed_at')->useCurrent();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
