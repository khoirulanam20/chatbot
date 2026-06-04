<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_instances', function (Blueprint $table) {
            $table->boolean('typing_enabled')->default(false)->after('status');
            $table->unsignedInteger('typing_duration_ms')->default(2000)->after('typing_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('wa_instances', function (Blueprint $table) {
            $table->dropColumn(['typing_enabled', 'typing_duration_ms']);
        });
    }
};
