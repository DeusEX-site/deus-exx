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
            // Переименовываем broker_name в recipient_name
            $table->renameColumn('broker_name', 'recipient_name');
            
            // Добавляем новые поля для стандартного формата
            $table->string('language')->nullable()->after('geos');
            $table->string('funnel')->nullable()->after('language');
            $table->boolean('pending_acq')->default(false)->after('funnel');
            $table->boolean('freeze_status_on_acq')->default(false)->after('pending_acq');
            
            // Добавляем индексы для новых полей
            $table->index('recipient_name');
            $table->index('language');
            $table->index('pending_acq');
            $table->index('freeze_status_on_acq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caps', function (Blueprint $table) {
            // Убираем новые поля
            $table->dropIndex(['recipient_name']);
            $table->dropIndex(['language']);
            $table->dropIndex(['pending_acq']);
            $table->dropIndex(['freeze_status_on_acq']);
            
            $table->dropColumn(['language', 'funnel', 'pending_acq', 'freeze_status_on_acq']);
            
            // Возвращаем старое название
            $table->renameColumn('recipient_name', 'broker_name');
        });
    }
}; 