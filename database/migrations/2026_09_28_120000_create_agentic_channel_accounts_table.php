<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_channel_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('channel', 32)->index();
            $table->string('driver', 32)->index();
            $table->string('agent_slug')->nullable()->index();
            $table->string('external_id')->nullable()->index();
            $table->string('display_number')->nullable();
            $table->json('config')->nullable();
            $table->text('credentials')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
            $table->unique(['channel', 'driver', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_channel_accounts');
    }
};
