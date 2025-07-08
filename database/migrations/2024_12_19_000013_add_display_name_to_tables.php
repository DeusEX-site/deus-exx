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
        Schema::table('chats', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('title');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }
}; 