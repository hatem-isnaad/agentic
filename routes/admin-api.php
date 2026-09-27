<?php

use Agentic\Http\Controllers\Admin\AgentController;
use Agentic\Http\Controllers\Admin\AiRegistryController;
use Agentic\Http\Controllers\Admin\ConversationController;
use Agentic\Http\Controllers\Admin\DashboardController;
use Agentic\Http\Controllers\Admin\ExecutionController;
use Agentic\Http\Controllers\Admin\KnowledgeSourceController;
use Agentic\Http\Controllers\Admin\LocaleController;
use Agentic\Http\Controllers\Admin\PackageSettingsController;
use Agentic\Http\Controllers\Admin\SkillController;
use Agentic\Http\Controllers\Admin\CodeToolHandlerController;
use Agentic\Http\Controllers\Admin\ToolController;
use Agentic\Http\Controllers\Admin\TranslationsController;
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
Route::get('settings', [PackageSettingsController::class, 'show'])->name('settings.show');
Route::get('ai-registry', AiRegistryController::class)->name('ai-registry');
Route::get('translations', TranslationsController::class)->name('translations');
Route::get('locale', [LocaleController::class, 'show'])->name('locale.show');
Route::put('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('agents', [AgentController::class, 'index'])->name('agents.index');
Route::post('agents', [AgentController::class, 'store'])->name('agents.store');
Route::get('agents/{slug}', [AgentController::class, 'show'])->name('agents.show');
Route::put('agents/{slug}', [AgentController::class, 'update'])->name('agents.update');
Route::delete('agents/{slug}', [AgentController::class, 'destroy'])->name('agents.destroy');
Route::post('agents/{slug}/execute', AgentExecuteController::class)->name('agents.execute');

Route::get('skills', [SkillController::class, 'index'])->name('skills.index');
Route::post('skills', [SkillController::class, 'store'])->name('skills.store');
Route::get('skills/{slug}', [SkillController::class, 'show'])->name('skills.show');
Route::put('skills/{slug}', [SkillController::class, 'update'])->name('skills.update');
Route::delete('skills/{slug}', [SkillController::class, 'destroy'])->name('skills.destroy');

Route::get('tools', [ToolController::class, 'index'])->name('tools.index');
Route::post('tools', [ToolController::class, 'store'])->name('tools.store');
Route::get('tools/{slug}', [ToolController::class, 'show'])->name('tools.show');
Route::put('tools/{slug}', [ToolController::class, 'update'])->name('tools.update');
Route::delete('tools/{slug}', [ToolController::class, 'destroy'])->name('tools.destroy');

Route::get('code-handlers', [CodeToolHandlerController::class, 'index'])->name('code-handlers.index');
Route::post('code-handlers/sync', [CodeToolHandlerController::class, 'sync'])->name('code-handlers.sync');
Route::post('code-handlers/publish', [CodeToolHandlerController::class, 'publish'])->name('code-handlers.publish');

Route::get('knowledge-sources', [KnowledgeSourceController::class, 'index'])->name('knowledge-sources.index');
Route::post('knowledge-sources', [KnowledgeSourceController::class, 'store'])->name('knowledge-sources.store');
Route::get('knowledge-sources/{slug}', [KnowledgeSourceController::class, 'show'])->name('knowledge-sources.show');
Route::put('knowledge-sources/{slug}', [KnowledgeSourceController::class, 'update'])->name('knowledge-sources.update');
Route::delete('knowledge-sources/{slug}', [KnowledgeSourceController::class, 'destroy'])->name('knowledge-sources.destroy');
Route::post('knowledge-sources/{slug}/index', [KnowledgeSourceController::class, 'indexDocuments'])->name('knowledge-sources.index-documents');
Route::post('knowledge-sources/{slug}/ingest', [KnowledgeSourceController::class, 'ingest'])->name('knowledge-sources.ingest');
Route::post('knowledge-sources/{slug}/search', [KnowledgeSourceController::class, 'search'])->name('knowledge-sources.search');

Route::get('widget-embed-tokens', [WidgetEmbedTokenController::class, 'index'])->name('widget-embed-tokens.index');
Route::post('widget-embed-tokens', [WidgetEmbedTokenController::class, 'store'])->name('widget-embed-tokens.store');
Route::put('widget-embed-tokens/{id}', [WidgetEmbedTokenController::class, 'update'])->name('widget-embed-tokens.update');
Route::delete('widget-embed-tokens/{id}', [WidgetEmbedTokenController::class, 'destroy'])->name('widget-embed-tokens.destroy');

Route::get('widget-settings/schema', [WidgetSettingsController::class, 'schema'])->name('widget-settings.schema');
Route::get('widget-settings', [WidgetSettingsController::class, 'index'])->name('widget-settings.index');
Route::get('widget-settings/{agentSlug}', [WidgetSettingsController::class, 'show'])->name('widget-settings.show');
Route::put('widget-settings/{agentSlug}', [WidgetSettingsController::class, 'update'])->name('widget-settings.update');
Route::delete('widget-settings/{agentSlug}', [WidgetSettingsController::class, 'destroy'])->name('widget-settings.destroy');

Route::get('executions', [ExecutionController::class, 'index'])->name('executions.index');
Route::get('executions/{id}', [ExecutionController::class, 'show'])->name('executions.show');

Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
Route::get('conversations/{id}', [ConversationController::class, 'show'])->name('conversations.show');
Route::get('conversations/{id}/messages', [ConversationController::class, 'messages'])->name('conversations.messages');

Route::get('workflows', [WorkflowController::class, 'index'])->name('workflows.index');
Route::post('workflows', [WorkflowController::class, 'store'])->name('workflows.store');
Route::get('workflows/{slug}', [WorkflowController::class, 'show'])->name('workflows.show');
Route::put('workflows/{slug}', [WorkflowController::class, 'update'])->name('workflows.update');
Route::delete('workflows/{slug}', [WorkflowController::class, 'destroy'])->name('workflows.destroy');
Route::post('workflows/{slug}/execute', WorkflowExecuteController::class)->name('workflows.execute');
Route::post('workflows/{slug}/resume', WorkflowResumeController::class)->name('workflows.resume');

Route::get('workflow-runs', [WorkflowRunController::class, 'index'])->name('workflow-runs.index');
Route::get('workflow-runs/{uuid}', [WorkflowRunController::class, 'show'])->name('workflow-runs.show');

Route::get('mcp/servers', [McpServerController::class, 'index'])->name('mcp.servers.index');
Route::post('mcp/servers/{server}/sync', [McpServerController::class, 'sync'])->name('mcp.servers.sync');
Route::get('mcp/servers/{server}/tools', [McpCatalogController::class, 'tools'])->name('mcp.servers.tools');
Route::get('mcp/servers/{server}/resources', [McpCatalogController::class, 'resources'])->name('mcp.servers.resources');
Route::get('mcp/servers/{server}/prompts', [McpCatalogController::class, 'prompts'])->name('mcp.servers.prompts');

Route::get('memories', [MemoryController::class, 'index'])->name('memories.index');
Route::post('memories', [MemoryController::class, 'store'])->name('memories.store');
Route::delete('memories/{id}', [MemoryController::class, 'destroy'])->name('memories.destroy');
