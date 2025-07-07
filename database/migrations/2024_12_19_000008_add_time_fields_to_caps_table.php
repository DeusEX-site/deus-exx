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
            $table->string('start_time')->nullable()->after('work_hours');
            $table->string('end_time')->nullable()->after('start_time');
            $table->string('timezone')->nullable()->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caps', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time', 'timezone']);
        });
    }
}; 