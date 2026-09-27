<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_conversation_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversation_id')->constrained('agentic_conversations')->cascadeOnDelete();
            $table->string('role', 32);
            $table->longText('content_html');
            $table->string('format', 32)->default('html');
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedInteger('tokens_total')->default(0);
            $table->string('locale', 12)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('agentic_tool_approvals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('execution_uuid')->nullable()->index();
            $table->string('conversation_uuid')->nullable()->index();
            $table->string('agent')->index();
            $table->string('tool')->index();
            $table->json('arguments')->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_tool_approvals');
        Schema::dropIfExists('agentic_conversation_messages');
    }
};
