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
        Schema::create('members', function (Blueprint $table) {
           $table->id();
           $table->foreignId('gimnasio_id')->constrained('gimnasios')->cascadeOnDelete();
           $table->string('name');
           $table->string('email')->nullable();
           $table->string('phone')->nullable();
           $table->date('birth_date')->nullable();
           $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
