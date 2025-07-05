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
            $table->unsignedBigInteger('cap_id'); // ID основной записи cap
            $table->unsignedBigInteger('message_id'); // ID сообщения для связи
            $table->string('action_type'); // 'created', 'updated', 'replaced'
            
            // Предыдущие значения (только для updated/replaced)
            $table->json('old_values')->nullable();
            
            // Новые значения
            $table->json('cap_amounts')->nullable();
            $table->integer('total_amount')->nullable();
            $table->string('schedule')->nullable();
            $table->string('date')->nullable();
            $table->boolean('is_24_7')->default(false);
            $table->string('affiliate_name')->nullable();
            $table->string('broker_name')->nullable();
            $table->json('geos')->nullable();
            $table->string('work_hours')->nullable();
            $table->text('highlighted_text')->nullable();
            
            // Метаданные
            $table->text('reason')->nullable(); // Причина изменения
            $table->string('updated_by')->nullable(); // Кто обновил (система/пользователь)
            $table->boolean('is_hidden')->default(true); // Скрыта в выпадающем списке по умолчанию
            
            $table->timestamps();
            
            // Индексы
            $table->index('cap_id');
            $table->index('message_id');
            $table->index('action_type');
            $table->index('is_hidden');
            $table->index(['affiliate_name', 'broker_name']);
            $table->index('created_at');
            
            // Связи
            $table->foreign('cap_id')->references('id')->on('caps')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
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