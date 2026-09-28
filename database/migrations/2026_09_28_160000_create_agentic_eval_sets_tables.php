<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_eval_sets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('agent_slug')->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('agentic_eval_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('eval_set_id')->constrained('agentic_eval_sets')->cascadeOnDelete();
            $table->text('question');
            $table->json('expect_contains')->nullable();
            $table->string('expect_tool')->nullable();
            $table->timestamps();
        });

        Schema::create('agentic_eval_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('eval_set_id')->constrained('agentic_eval_sets')->cascadeOnDelete();
            $table->string('agent_slug')->index();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('passed')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->string('status', 32)->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('agentic_eval_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('eval_run_id')->constrained('agentic_eval_runs')->cascadeOnDelete();
            $table->foreignId('eval_case_id')->constrained('agentic_eval_cases')->cascadeOnDelete();
            $table->boolean('passed')->default(false);
            $table->text('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_eval_results');
        Schema::dropIfExists('agentic_eval_runs');
        Schema::dropIfExists('agentic_eval_cases');
        Schema::dropIfExists('agentic_eval_sets');
    }
};
