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
        Schema::table('memberships', function (Blueprint $table) {
                $table->decimal('outstanding_balance', 10, 2)->default(0);
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
             $table->dropColumn('outstanding_balance');
        });
    }
};
