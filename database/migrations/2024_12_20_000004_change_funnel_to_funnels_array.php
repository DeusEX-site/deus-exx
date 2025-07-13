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
            // Переименовываем funnel в funnels и меняем тип на JSON
            $table->renameColumn('funnel', 'funnels');
        });
        
        Schema::table('caps', function (Blueprint $table) {
            // Изменяем тип на JSON для хранения массива
            $table->json('funnels')->nullable()->change();
        });
        
        Schema::table('caps_history', function (Blueprint $table) {
            // Переименовываем funnel в funnels и меняем тип на JSON
            $table->renameColumn('funnel', 'funnels');
        });
        
        Schema::table('caps_history', function (Blueprint $table) {
            // Изменяем тип на JSON для хранения массива
            $table->json('funnels')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caps', function (Blueprint $table) {
            // Возвращаем обратно к string и переименовываем обратно
            $table->string('funnels')->nullable()->change();
        });
        
        Schema::table('caps', function (Blueprint $table) {
            $table->renameColumn('funnels', 'funnel');
        });
        
        Schema::table('caps_history', function (Blueprint $table) {
            // Возвращаем обратно к string и переименовываем обратно
            $table->string('funnels')->nullable()->change();
        });
        
        Schema::table('caps_history', function (Blueprint $table) {
            $table->renameColumn('funnels', 'funnel');
        });
    }
}; 