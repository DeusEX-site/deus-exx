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
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_id')->nullable()->after('id');
            $table->bigInteger('telegram_message_id')->nullable()->after('chat_id');
            $table->bigInteger('telegram_user_id')->nullable()->after('telegram_message_id');
            $table->string('telegram_username')->nullable()->after('telegram_user_id');
            $table->string('telegram_first_name')->nullable()->after('telegram_username');
            $table->string('telegram_last_name')->nullable()->after('telegram_first_name');
            $table->timestamp('telegram_date')->nullable()->after('telegram_last_name');
            $table->string('message_type', 50)->default('text')->after('telegram_date'); // text, photo, document, etc.
            $table->json('telegram_raw_data')->nullable()->after('message_type');
            
            $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
            $table->index(['chat_id', 'created_at']);
            $table->index('telegram_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['chat_id']);
            $table->dropIndex(['chat_id', 'created_at']);
            $table->dropIndex(['telegram_message_id']);
            $table->dropColumn([
                'chat_id',
                'telegram_message_id', 
                'telegram_user_id',
                'telegram_username',
                'telegram_first_name',
                'telegram_last_name',
                'telegram_date',
                'message_type',
                'telegram_raw_data'
            ]);
        });
    }
}; 