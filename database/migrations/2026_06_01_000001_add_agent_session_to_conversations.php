<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('agent_session_started_at')->nullable()->after('is_ai_active');
            $table->timestamp('agent_session_ends_at')->nullable()->after('agent_session_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['agent_session_started_at', 'agent_session_ends_at']);
        });
    }
};
