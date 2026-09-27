<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_widget_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('agent_slug')->unique();
            $table->json('settings');
            $table->timestamps();
        });

        Schema::create('agentic_broadcast_events', function (Blueprint $table): void {
            $table->id();
            $table->string('channel')->index();
            $table->string('event');
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['channel', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_broadcast_events');
        Schema::dropIfExists('agentic_widget_settings');
    }
};
