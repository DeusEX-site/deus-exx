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
        Schema::create('caps_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cap_id'); // ID оригинальной капы
            $table->unsignedBigInteger('message_id'); // ID сообщения из которого была создана историческая запись
            
            // Все поля из таблицы caps
            $table->json('cap_amounts')->nullable();
            $table->integer('total_amount')->nullable();
            $table->string('schedule')->nullable();
            $table->string('date')->nullable();
            $table->boolean('is_24_7')->default(false);
            $table->string('affiliate_name')->nullable();
            $table->string('recipient_name')->nullable();
            $table->json('geos')->nullable();
            $table->string('work_hours')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('timezone')->nullable();
            $table->string('language')->nullable();
            $table->string('funnel')->nullable();
            $table->boolean('pending_acq')->default(false);
            $table->boolean('freeze_status_on_acq')->default(false);
            $table->text('highlighted_text')->nullable();
            
            // Временные метки истории
            $table->timestamp('archived_at'); // Когда была заархивирована
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index('cap_id');
            $table->index('message_id');
            $table->index('affiliate_name');
            $table->index('recipient_name');
            $table->index('archived_at');
            
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
        Schema::dropIfExists('caps_history');
    }
}; 