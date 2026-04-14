<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('original_name');
            $table->string('type');
            $table->string('path');
            $table->enum('status', ['queued', 'processing', 'indexed', 'failed'])->default('queued');
            $table->integer('chunk_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('tags')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $table->text('content');
            $table->longText('embedding')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('chunk_index')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');
    }
};
