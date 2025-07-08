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
        Schema::table('caps_history', function (Blueprint $table) {
            $table->unsignedBigInteger('original_message_id')->nullable()->after('message_id');
            
            // Добавляем внешний ключ для связи с таблицей messages
            $table->foreign('original_message_id')->references('id')->on('messages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caps_history', function (Blueprint $table) {
            $table->dropForeign(['original_message_id']);
            $table->dropColumn('original_message_id');
        });
    }
}; 