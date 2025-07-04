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
        Schema::create('caps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->json('cap_amounts')->nullable(); // Массив всех найденных кап
            $table->integer('total_amount')->nullable(); // Общий лимит
            $table->string('schedule')->nullable(); // Расписание (24/7, 10-19, etc.)
            $table->string('date')->nullable(); // Дата (14.05, etc.)
            $table->boolean('is_24_7')->default(false); // Работает ли 24/7
            $table->string('affiliate_name')->nullable(); // Имя аффилейта
            $table->string('broker_name')->nullable(); // Имя брокера
            $table->json('geos')->nullable(); // Массив гео
            $table->string('work_hours')->nullable(); // Часы работы
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index('message_id');
            $table->index('is_24_7');
            $table->index('affiliate_name');
            $table->index('broker_name');
            
            // Связь с таблицей messages
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caps');
    }
}; 