<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentic_vector_entries', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('namespace')->index();
            $table->json('vector');
            $table->text('content');
            $table->string('source')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agentic_vector_entries');
    }
};
