<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_conversations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('agent')->index();
            $table->string('sdk_conversation_id')->nullable()->index();
            $table->string('user_id')->nullable()->index();
            $table->string('tenant_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['agent', 'user_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_conversations');
    }
};
