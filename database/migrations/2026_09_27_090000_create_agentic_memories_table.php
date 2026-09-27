<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_memories', function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 32)->index();
            $table->string('scope_key', 191)->index();
            $table->string('agent_slug', 191)->nullable()->index();
            $table->string('key', 191);
            $table->text('content');
            $table->unsignedTinyInteger('importance')->default(5);
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['scope', 'scope_key', 'agent_slug', 'key']);
            $table->index(['scope', 'scope_key', 'importance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_memories');
    }
};
