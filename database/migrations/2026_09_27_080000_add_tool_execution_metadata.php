<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agentic_execution_steps', function (Blueprint $table): void {
            $table->unsignedBigInteger('tool_id')->nullable()->after('execution_id');
            $table->unsignedBigInteger('tool_version_id')->nullable()->after('tool_id');
            $table->boolean('permission_allowed')->nullable()->after('status');
            $table->unsignedInteger('duration_ms')->nullable()->after('completed_at');
            $table->index(['tool_id', 'tool_version_id']);
        });
    }

    public function down(): void
    {
        Schema::table('agentic_execution_steps', function (Blueprint $table): void {
            $table->dropIndex(['tool_id', 'tool_version_id']);
            $table->dropColumn(['tool_id', 'tool_version_id', 'permission_allowed', 'duration_ms']);
        });
    }
};
