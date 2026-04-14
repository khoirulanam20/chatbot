<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->text('system_prompt')->nullable();
            $table->string('model')->default('gpt-4o');
            $table->float('temperature', 3, 2)->default(0.7);
            $table->integer('max_context')->default(10);
            $table->string('language')->default('id');
            $table->text('fallback_message')->nullable();
            $table->json('handoff_triggers')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bot_embed_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained()->cascadeOnDelete();
            $table->string('primary_color')->default('#4F46E5');
            $table->string('position')->default('bottom-right');
            $table->string('size')->default('normal');
            $table->string('greeting')->nullable();
            $table->json('quick_replies')->nullable();
            $table->json('branding')->nullable();
            $table->boolean('sound_enabled')->default(false);
            $table->integer('auto_open_delay')->nullable();
            $table->boolean('allow_file_upload')->default(false);
            $table->timestamps();
        });

        Schema::create('wa_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chatbot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('instance_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('api_key')->nullable();
            $table->string('status')->default('inactive');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_instances');
        Schema::dropIfExists('bot_embed_configs');
        Schema::dropIfExists('chatbots');
    }
};
