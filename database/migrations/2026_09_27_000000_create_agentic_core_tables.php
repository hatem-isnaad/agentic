<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_agents', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->json('model_config')->nullable();
            $table->json('config')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('agentic_skills', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->json('config')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('agentic_tools', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type', 32)->index();
            $table->string('driver', 128);
            $table->string('implementation')->nullable();
            $table->json('config')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('agentic_tool_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tool_id')->constrained('agentic_tools')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('definition');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['tool_id', 'version']);
            $table->index(['tool_id', 'published_at']);
        });

        Schema::create('agentic_agent_skill', function (Blueprint $table): void {
            $table->foreignId('agent_id')->constrained('agentic_agents')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('agentic_skills')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->primary(['agent_id', 'skill_id']);
            $table->index(['agent_id', 'position']);
        });

        Schema::create('agentic_skill_tool', function (Blueprint $table): void {
            $table->foreignId('skill_id')->constrained('agentic_skills')->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained('agentic_tools')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->primary(['skill_id', 'tool_id']);
            $table->index(['skill_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_skill_tool');
        Schema::dropIfExists('agentic_agent_skill');
        Schema::dropIfExists('agentic_tool_versions');
        Schema::dropIfExists('agentic_tools');
        Schema::dropIfExists('agentic_skills');
        Schema::dropIfExists('agentic_agents');
    }
};
