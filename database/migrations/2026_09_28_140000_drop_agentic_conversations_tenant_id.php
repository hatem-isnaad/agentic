<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agentic_conversations') || ! Schema::hasColumn('agentic_conversations', 'tenant_id')) {
            return;
        }

        $indexNames = array_map(
            static fn (array $index): string => (string) ($index['name'] ?? ''),
            Schema::getIndexes('agentic_conversations'),
        );

        Schema::table('agentic_conversations', function (Blueprint $table) use ($indexNames): void {
            if (in_array('agentic_conversations_agent_user_id_tenant_id_index', $indexNames, true)) {
                $table->dropIndex('agentic_conversations_agent_user_id_tenant_id_index');
            }

            if (in_array('agentic_conversations_tenant_id_index', $indexNames, true)) {
                $table->dropIndex('agentic_conversations_tenant_id_index');
            }

            $table->dropColumn('tenant_id');

            if (! in_array('agentic_conversations_agent_user_id_index', $indexNames, true)) {
                $table->index(['agent', 'user_id']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('agentic_conversations') || Schema::hasColumn('agentic_conversations', 'tenant_id')) {
            return;
        }

        Schema::table('agentic_conversations', function (Blueprint $table): void {
            $table->string('tenant_id')->nullable()->index();
        });
    }
};
