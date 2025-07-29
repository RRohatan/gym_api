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
           Schema::table('payments', function (Blueprint $table) {
        $table->dropColumn('payment_method_id'); // elimina el enum

        // crea el campo como clave foránea
        $table->unsignedBigInteger('payment_method_id')->after('paymentable_type');
        $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
        $table->dropForeign(['payment_method_id']);
        $table->dropColumn('payment_method_id');

        // puedes restaurar el enum si quieres revertir
        $table->enum('payment_method_id', ['efectivo', 'tarjeta', 'nequi', 'daviplata','otro'])->default('efectivo');
    });
    }
};
