<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('supplement_sales', function (Blueprint $table) {
            // Agregamos la columna que falta
            $table->dateTime('paid_at')->nullable()->after('total');
        });
    }

    public function down()
    {
        Schema::table('supplement_sales', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};
