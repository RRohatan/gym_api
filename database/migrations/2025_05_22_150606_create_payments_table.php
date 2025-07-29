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
        Schema::create('payments', function (Blueprint $table) {
           $table->id();
           $table->decimal('amount', 10, 2);
           $table->unsignedBigInteger('paymentable_id');
           $table->string('paymentable_type');
           $table->enum('payment_method_id', ['efectivo', 'tarjeta', 'nequi', 'daviplata','otro'])->default('efectivo');
           $table->dateTime('paid_at')->nullable();
           $table->timestamps();

           $table->index(['paymentable_id', 'paymentable_type']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
