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
            $table->string('test')->nullable()->after('funnel');
            $table->index('test');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caps', function (Blueprint $table) {
            $table->dropIndex(['test']);
            $table->dropColumn('test');
        });
    }
}; 