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
        Schema::create('daily_cashboxes', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique(); // Una caja por día
            $table->decimal('opening_balance', 10, 2)->default(0); // Saldo inicial (opcional)
            $table->decimal('total_income', 10, 2)->default(0);     // Total cobrado
            $table->decimal('closing_balance', 10, 2)->default(0);  // Saldo final (calculado)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_cashboxes');
    }
};
