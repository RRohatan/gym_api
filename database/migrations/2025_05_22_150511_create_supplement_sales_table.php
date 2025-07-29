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
        Schema::create('supplement_sales', function (Blueprint $table) {
          $table->id();
          $table->foreignId('member_id')->constrained()->cascadeOnDelete();
          $table->foreignId('product_id')->constrained('supplement_products')->cascadeOnDelete();
          $table->integer('quantity');
          $table->decimal('total', 10, 2);
          $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplement_sales');
    }
};
