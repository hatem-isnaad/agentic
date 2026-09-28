<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->string('conversation_id')->nullable()->index();
            $table->string('execution_id')->nullable()->index();
            $table->string('agent_slug')->nullable()->index();
            $table->unsignedTinyInteger('score');
            $table->string('label', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_evaluations');
    }
};
