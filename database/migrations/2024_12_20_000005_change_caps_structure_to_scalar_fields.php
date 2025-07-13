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
        Schema::table('caps', function (Blueprint $table) {
            // Удаляем старые поля с массивами
            $table->dropColumn(['cap_amounts', 'geos', 'funnels']);
            
            // Добавляем новые скалярные поля
            $table->integer('cap_amount')->nullable();
            $table->string('geo', 10)->nullable();
            $table->string('funnel', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caps', function (Blueprint $table) {
            // Удаляем новые поля
            $table->dropColumn(['cap_amount', 'geo', 'funnel']);
            
            // Возвращаем старые поля с массивами
            $table->json('cap_amounts')->nullable();
            $table->json('geos')->nullable();
            $table->json('funnels')->nullable();
        });
    }
}; 