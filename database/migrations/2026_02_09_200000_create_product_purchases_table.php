<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('supplement_products')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('total_cost', 10, 2);
            $table->string('supplier')->nullable();
            $table->timestamp('purchase_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_purchases');
    }
};
