<?php

use Agentic\Http\Controllers\Api\AgentController;
use Agentic\Http\Controllers\Api\AgentExecuteController;
use Agentic\Http\Controllers\Api\AgentRouteController;
use Agentic\Http\Controllers\Api\ConversationController;
use Agentic\Http\Controllers\Api\ExecutionController;
use Agentic\Http\Controllers\Api\KnowledgeSourceController;
use Agentic\Http\Controllers\Api\McpCatalogController;
use Agentic\Http\Controllers\Api\McpServerController;
use Agentic\Http\Controllers\Api\MemoryController;
use Agentic\Http\Controllers\Api\SkillController;
use Agentic\Http\Controllers\Api\ToolController;
use Agentic\Http\Controllers\Api\WorkflowController;
use Agentic\Http\Controllers\Api\WorkflowExecuteController;
use Agentic\Http\Controllers\Api\WorkflowResumeController;
use Agentic\Http\Controllers\Api\WorkflowRunController;
use Illuminate\Support\Facades\Route;

Route::apiResource('agents', AgentController::class)->parameters(['agents' => 'slug']);
Route::post('agents/{slug}/execute', AgentExecuteController::class)->name('agents.execute');
Route::post('route', AgentRouteController::class)->name('route');

Route::get('executions/{id}', [ExecutionController::class, 'show'])->name('executions.show');
Route::get('conversations/{id}', [ConversationController::class, 'show'])->name('conversations.show');

Route::apiResource('skills', SkillController::class)->parameters(['skills' => 'slug']);
Route::apiResource('tools', ToolController::class)->parameters(['tools' => 'slug']);

Route::apiResource('knowledge-sources', KnowledgeSourceController::class)->parameters(['knowledge-sources' => 'slug']);
Route::post('knowledge-sources/{slug}/index', [KnowledgeSourceController::class, 'indexDocuments'])->name('knowledge-sources.index-documents');
Route::post('knowledge-sources/{slug}/ingest', [KnowledgeSourceController::class, 'ingest'])->name('knowledge-sources.ingest');

Route::get('mcp/servers', [McpServerController::class, 'index'])->name('mcp.servers.index');
Route::post('mcp/servers/{server}/sync', [McpServerController::class, 'sync'])->name('mcp.servers.sync');
Route::get('mcp/servers/{server}/tools', [McpCatalogController::class, 'tools'])->name('mcp.servers.tools');
Route::get('mcp/servers/{server}/resources', [McpCatalogController::class, 'resources'])->name('mcp.servers.resources');
Route::post('mcp/servers/{server}/resources/read', [McpCatalogController::class, 'readResource'])->name('mcp.servers.resources.read');
Route::get('mcp/servers/{server}/prompts', [McpCatalogController::class, 'prompts'])->name('mcp.servers.prompts');
Route::post('mcp/servers/{server}/prompts/{name}', [McpCatalogController::class, 'prompt'])->name('mcp.servers.prompts.show');

Route::apiResource('workflows', WorkflowController::class)->parameters(['workflows' => 'slug']);
Route::post('workflows/{slug}/execute', WorkflowExecuteController::class)->name('workflows.execute');
Route::post('workflows/{slug}/resume', WorkflowResumeController::class)->name('workflows.resume');
Route::apiResource('workflow-runs', WorkflowRunController::class)->only(['index', 'show'])->parameters(['workflow-runs' => 'uuid']);

Route::apiResource('memories', MemoryController::class)->only(['index', 'store', 'destroy'])->parameters(['memories' => 'id']);
