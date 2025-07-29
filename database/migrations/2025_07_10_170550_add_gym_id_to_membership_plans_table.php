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
            $table->unsignedBigInteger('gym_id')->after('id');
            $table->foreign('gym_id')->references('id')->on('gimnasios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
             $table->dropForeign(['gym_id']);
             $table->dropColumn('gym_id');
        });
    }
};
