<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_executions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('agent');
            $table->string('status', 32)->index();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->string('conversation_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['agent', 'status']);
        });

        Schema::create('agentic_execution_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('execution_id')->constrained('agentic_executions')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('type', 64)->index();
            $table->string('status', 32)->index();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_execution_steps');
        Schema::dropIfExists('agentic_executions');
    }
};
