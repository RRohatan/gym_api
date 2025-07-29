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
        Schema::table('payments', function(Blueprint $table){
           $table->unsignedBigInteger('cashbox_id')->nullable();
           $table->foreign('cashbox_id')->references('id')->on('daily_cashboxes')->onDelete('set null');
        });

        Schema::table('supplement_sales', function(Blueprint $table){
           $table->unsignedBigInteger('cashbox_id')->nullable();
           $table->foreign('cashbox_id')->references('id')->on('daily_cashboxes')->onDelete('set null');
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->unsignedBigInteger('cashbox_id')->nullable();
            $table->foreign('cashbox_id')->references('id')->on('daily_cashboxes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
  public function down(): void
{
    Schema::table('payments', function(Blueprint $table){
        $table->dropForeign(['cashbox_id']);
        $table->dropColumn('cashbox_id');
    });

    Schema::table('supplement_sales', function(Blueprint $table){
        $table->dropForeign(['cashbox_id']);
        $table->dropColumn('cashbox_id');
    });

    Schema::table('gastos', function(Blueprint $table){
        $table->dropForeign(['cashbox_id']);
        $table->dropColumn('cashbox_id');
    });
}

};
