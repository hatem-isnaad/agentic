<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_widget_embed_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token_prefix', 16)->index();
            $table->string('token_hash', 64)->unique();
            $table->json('allowed_agents')->nullable();
            $table->json('allowed_origins')->nullable();
            $table->boolean('guest_allowed')->default(true);
            $table->boolean('sanctum_allowed')->default(true);
            $table->boolean('enabled')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_widget_embed_tokens');
    }
};
