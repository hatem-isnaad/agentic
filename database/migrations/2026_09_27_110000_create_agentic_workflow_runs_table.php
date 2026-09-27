<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_workflow_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('workflow_slug')->index();
            $table->string('status', 32)->index();
            $table->unsignedInteger('step_pointer')->default(0);
            $table->json('variables')->nullable();
            $table->json('trace')->nullable();
            $table->uuid('approval_uuid')->nullable()->index();
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_workflow_runs');
    }
};
