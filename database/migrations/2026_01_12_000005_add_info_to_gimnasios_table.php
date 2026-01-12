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
    Schema::table('gimnasios', function (Blueprint $table) {
        $table->text('horarios')->nullable();       // Ej: Lunes a Viernes 6am - 10pm
        $table->text('politicas')->nullable();      // Ej: Uso de toalla obligatorio
        $table->string('url_grupo_whatsapp')->nullable(); // Link de invitación al grupo
    });
}

public function down()
{
    Schema::table('gimnasios', function (Blueprint $table) {
        $table->dropColumn(['horarios', 'politicas', 'url_grupo_whatsapp']);
    });
}
};
