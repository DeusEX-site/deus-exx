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
            $table->enum('status', ['RUN', 'STOP', 'DELETE'])->default('RUN')->after('highlighted_text');
            $table->timestamp('status_updated_at')->nullable()->after('status');
            
            // Добавляем индекс для быстрого поиска активных кап
            $table->index(['status', 'affiliate_name', 'recipient_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caps', function (Blueprint $table) {
            $table->dropIndex(['status', 'affiliate_name', 'recipient_name']);
            $table->dropColumn(['status', 'status_updated_at']);
        });
    }
}; 