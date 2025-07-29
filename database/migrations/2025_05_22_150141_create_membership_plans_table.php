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
        Schema::create('membership_plans', function (Blueprint $table) {
          $table->id();
          $table->foreignId('membership_type_id')->constrained()->cascadeOnDelete();
          $table->enum('frequency', ['weekly', 'biweekly', 'monthly']);
          $table->decimal('price', 10, 2);
          $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
