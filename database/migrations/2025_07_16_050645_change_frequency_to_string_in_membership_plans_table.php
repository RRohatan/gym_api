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
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->string('frequency')->change();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            //
        });
    }
};
