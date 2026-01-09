<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    // 1. Desactivar protecciones para poder borrar sin problemas
    Schema::disableForeignKeyConstraints();

    // 2. Si hay ventas de suplementos vinculadas a cajas viejas, las limpiamos
    // (Esto es necesario porque vamos a borrar las cajas a las que apuntan)
    DB::table('supplement_sales')->truncate();

    // 3. BORRAR la tabla problemática por completo
    Schema::dropIfExists('daily_cashboxes');

    // 4. CREAR la tabla de nuevo desde cero (con la estructura final correcta)
    Schema::create('daily_cashboxes', function (Blueprint $table) {
        $table->id();

        // Campos originales
        $table->date('date');
        $table->decimal('opening_balance', 15, 2); // Decimal para dinero

        // Campo nuevo
        $table->foreignId('gimnasio_id')
              ->nullable()
              ->constrained('gimnasios')
              ->onDelete('cascade');

        $table->timestamps();

        // Definimos la regla de unicidad correcta desde el nacimiento
        $table->unique(['date', 'gimnasio_id']);
    });

    // Reactivar protecciones
    Schema::enableForeignKeyConstraints();
}


    public function down(): void
    {
        Schema::table('daily_cashboxes', function (Blueprint $table) {
            // 1. Borrar la restricción compuesta
            $table->dropUnique(['date', 'gimnasio_id']);

            // 2. Borrar la llave foránea y la columna
            $table->dropForeign(['gimnasio_id']);
            $table->dropColumn('gimnasio_id');

            // 3. Restaurar la restricción simple (opcional, si quisieras volver atrás)
            $table->unique('date');
        });
    }
};
