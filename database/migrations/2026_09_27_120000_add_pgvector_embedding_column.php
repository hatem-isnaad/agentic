<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $dimensions = (int) config('agentic.knowledge.pgvector.dimensions', 1536);

        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        DB::statement(
            "ALTER TABLE agentic_vector_entries ADD COLUMN IF NOT EXISTS embedding vector({$dimensions})",
        );

    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE agentic_vector_entries DROP COLUMN IF EXISTS embedding');
    }
};
