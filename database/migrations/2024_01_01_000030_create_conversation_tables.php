<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('identifier');
            $table->enum('channel', ['web', 'whatsapp']);
            $table->string('name')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_blacklisted')->default(false);
            $table->timestamps();
            $table->unique(['tenant_id', 'identifier', 'channel']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->unique();
            $table->enum('channel', ['web', 'whatsapp'])->default('web');
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['open', 'handoff', 'resolved', 'spam'])->default('open');
            $table->boolean('is_ai_active')->default(true);
            $table->timestamp('last_message_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['chatbot_id', 'status']);
            $table->index(['channel', 'status']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'agent', 'system'])->default('user');
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->integer('tokens')->nullable();
            $table->json('sources')->nullable();
            $table->timestamps();
            $table->index('conversation_id');
        });

        Schema::create('message_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('rating');
            $table->text('feedback')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_handoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('trigger_keyword')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_handoffs');
        Schema::dropIfExists('message_ratings');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('contacts');
    }
};
