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
        Schema::create('cap_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cap_id'); // ID капы которая была изменена
            $table->unsignedBigInteger('source_message_id'); // ID сообщения которое содержит новые настройки
            $table->unsignedBigInteger('target_message_id'); // ID сообщения которое было обновлено
            $table->string('match_key'); // Ключ совпадения (affiliate-broker-geo)
            
            // Старые значения
            $table->json('old_values')->nullable();
            
            // Новые значения
            $table->json('new_values')->nullable();
            
            // Что именно было изменено
            $table->json('changed_fields')->nullable();
            
            $table->string('action')->default('updated'); // updated, created, etc.
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index('cap_id');
            $table->index('source_message_id');
            $table->index('target_message_id');
            $table->index('match_key');
            $table->index(['cap_id', 'created_at']);
            
            // Внешние ключи
            $table->foreign('cap_id')->references('id')->on('caps')->onDelete('cascade');
            $table->foreign('source_message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('target_message_id')->references('id')->on('messages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cap_history');
    }
}; 