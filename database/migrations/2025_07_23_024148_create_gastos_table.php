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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gimnasio_id')->constrained('gimnasios')->onDelete('cascade');
            $table->string('concepto'); // Ej: "Compra agua", "Pago entrenador"
            $table->decimal('monto', 12, 2);
            $table->date('fecha'); // Fecha del gasto
            $table->text('descripcion')->nullable();
            $table->timestamps(); // created_at = cuándo se registró
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
