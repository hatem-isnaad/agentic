<?php

use Agentic\Http\Controllers\Admin\AgentController;
use Agentic\Http\Controllers\Admin\AiRegistryController;
use Agentic\Http\Controllers\Admin\ChannelAccountController;
use Agentic\Http\Controllers\Admin\CodeToolHandlerController;
use Agentic\Http\Controllers\Admin\ConnectionController;
use Agentic\Http\Controllers\Admin\ConversationAttachmentController;
use Agentic\Http\Controllers\Admin\ConversationController;
use Agentic\Http\Controllers\Admin\DashboardController;
use Agentic\Http\Controllers\Admin\EvalSetController;
use Agentic\Http\Controllers\Admin\EvaluationController;
use Agentic\Http\Controllers\Admin\ExecutionController;
use Agentic\Http\Controllers\Admin\InboxController;
use Agentic\Http\Controllers\Admin\KnowledgeSourceController;
use Agentic\Http\Controllers\Admin\LocaleController;
use Agentic\Http\Controllers\Admin\PackageSettingsController;
use Agentic\Http\Controllers\Admin\SkillController;
use Agentic\Http\Controllers\Admin\ToolController;
use Agentic\Http\Controllers\Admin\TranslationsController;
use Agentic\Http\Controllers\Admin\UsageController;
use Agentic\Http\Controllers\Admin\WidgetEmbedTokenController;
use Agentic\Http\Controllers\Admin\WidgetSettingsController;
use Agentic\Http\Controllers\Admin\WorkflowController;
use Agentic\Http\Controllers\Admin\WorkflowRunController;
use Agentic\Http\Controllers\Api\AgentExecuteController;
use Agentic\Http\Controllers\Api\McpCatalogController;
use Agentic\Http\Controllers\Api\McpServerController;
use Agentic\Http\Controllers\Api\MemoryController;
use Agentic\Http\Controllers\Api\WorkflowExecuteController;
use Agentic\Http\Controllers\Api\WorkflowResumeController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', DashboardController::class)->name('dashboard');
Route::get('usage', UsageController::class)->name('usage');
Route::get('settings', [PackageSettingsController::class, 'show'])->name('settings.show');
Route::get('ai-registry', AiRegistryController::class)->name('ai-registry');
Route::get('translations', TranslationsController::class)->name('translations');
Route::get('locale', [LocaleController::class, 'show'])->name('locale.show');
Route::put('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::apiResource('agents', AgentController::class)->parameters(['agents' => 'slug']);
Route::post('agents/{slug}/execute', AgentExecuteController::class)->name('agents.execute');

Route::apiResource('skills', SkillController::class)->parameters(['skills' => 'slug']);
Route::apiResource('tools', ToolController::class)->parameters(['tools' => 'slug']);
Route::post('tools/{slug}/test', [ToolController::class, 'test'])->name('tools.test');
Route::post('tools/{slug}/clone', [ToolController::class, 'clone'])->name('tools.clone');

Route::get('code-handlers', [CodeToolHandlerController::class, 'index'])->name('code-handlers.index');
Route::post('code-handlers/sync', [CodeToolHandlerController::class, 'sync'])->name('code-handlers.sync');
Route::post('code-handlers/publish', [CodeToolHandlerController::class, 'publish'])->name('code-handlers.publish');

Route::apiResource('knowledge-sources', KnowledgeSourceController::class)->parameters(['knowledge-sources' => 'slug']);
Route::post('knowledge-sources/{slug}/index', [KnowledgeSourceController::class, 'indexDocuments'])->name('knowledge-sources.index-documents');
Route::post('knowledge-sources/{slug}/ingest', [KnowledgeSourceController::class, 'ingest'])->name('knowledge-sources.ingest');
Route::post('knowledge-sources/{slug}/search', [KnowledgeSourceController::class, 'search'])->name('knowledge-sources.search');

Route::apiResource('widget-embed-tokens', WidgetEmbedTokenController::class)->except('show')->parameters(['widget-embed-tokens' => 'id']);
Route::post('connections/{id}/refresh', [ConnectionController::class, 'refresh'])->name('connections.refresh');
Route::apiResource('connections', ConnectionController::class)->parameters(['connections' => 'id']);
Route::get('channel-accounts/options', [ChannelAccountController::class, 'options'])->name('channel-accounts.options');
Route::apiResource('channel-accounts', ChannelAccountController::class)->parameters(['channel-accounts' => 'id']);
Route::apiResource('evaluations', EvaluationController::class)->only(['index', 'store']);
Route::get('inbox', [InboxController::class, 'index'])->name('inbox.index');
Route::post('conversations/{id}/take', [InboxController::class, 'take'])->name('conversations.take');
Route::post('conversations/{id}/release', [InboxController::class, 'release'])->name('conversations.release');
Route::post('conversations/{id}/reply', [InboxController::class, 'reply'])->name('conversations.reply');
Route::get('eval-sets', [EvalSetController::class, 'index'])->name('eval-sets.index');
Route::post('eval-sets', [EvalSetController::class, 'store'])->name('eval-sets.store');
Route::get('eval-sets/{slug}', [EvalSetController::class, 'show'])->name('eval-sets.show');
Route::post('eval-sets/{slug}/cases', [EvalSetController::class, 'addCase'])->name('eval-sets.cases');
Route::post('eval-sets/{slug}/run', [EvalSetController::class, 'run'])->name('eval-sets.run');

Route::get('widget-settings/schema', [WidgetSettingsController::class, 'schema'])->name('widget-settings.schema');
Route::apiResource('widget-settings', WidgetSettingsController::class)->except('store')->parameters(['widget-settings' => 'agentSlug']);

Route::apiResource('executions', ExecutionController::class)->only(['index', 'show'])->parameters(['executions' => 'id']);
Route::apiResource('conversations', ConversationController::class)->only(['index', 'show'])->parameters(['conversations' => 'id']);
Route::get('conversations/{id}/messages', [ConversationController::class, 'messages'])->name('conversations.messages');
Route::get('conversations/{id}/files/{file}', [ConversationAttachmentController::class, 'show'])
    ->where('file', '[A-Za-z0-9._-]+')
    ->name('conversations.files.show');

Route::apiResource('workflows', WorkflowController::class)->parameters(['workflows' => 'slug']);
Route::post('workflows/{slug}/execute', WorkflowExecuteController::class)->name('workflows.execute');
Route::post('workflows/{slug}/resume', WorkflowResumeController::class)->name('workflows.resume');

Route::apiResource('workflow-runs', WorkflowRunController::class)->only(['index', 'show'])->parameters(['workflow-runs' => 'uuid']);

Route::get('mcp/servers', [McpServerController::class, 'index'])->name('mcp.servers.index');
Route::post('mcp/servers/{server}/sync', [McpServerController::class, 'sync'])->name('mcp.servers.sync');
Route::get('mcp/servers/{server}/tools', [McpCatalogController::class, 'tools'])->name('mcp.servers.tools');
Route::get('mcp/servers/{server}/resources', [McpCatalogController::class, 'resources'])->name('mcp.servers.resources');
Route::get('mcp/servers/{server}/prompts', [McpCatalogController::class, 'prompts'])->name('mcp.servers.prompts');

Route::apiResource('memories', MemoryController::class)->only(['index', 'store', 'destroy'])->parameters(['memories' => 'id']);
