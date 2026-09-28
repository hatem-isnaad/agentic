<?php

use Agentic\Http\Controllers\Admin\Web\AgentWebController;
use Agentic\Http\Controllers\Admin\Web\ConversationWebController;
use Agentic\Http\Controllers\Admin\Web\DashboardWebController;
use Agentic\Http\Controllers\Admin\Web\ExecutionWebController;
use Agentic\Http\Controllers\Admin\Web\KnowledgeSourceWebController;
use Agentic\Http\Controllers\Admin\Web\LocaleWebController;
use Agentic\Http\Controllers\Admin\Web\SkillWebController;
use Agentic\Http\Controllers\Admin\Web\ToolWebController;
use Agentic\Http\Controllers\Admin\Web\WidgetSettingsWebController;
use Agentic\Http\Controllers\Admin\Web\WorkflowRunWebController;
use Agentic\Http\Controllers\Admin\Web\WorkflowWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardWebController::class)->name('dashboard');
Route::post('locale', [LocaleWebController::class, 'update'])->name('locale.update');

Route::resource('agents', AgentWebController::class)->parameters(['agents' => 'slug']);
Route::resource('skills', SkillWebController::class)->parameters(['skills' => 'slug']);
Route::resource('tools', ToolWebController::class)->parameters(['tools' => 'slug']);

Route::resource('knowledge-sources', KnowledgeSourceWebController::class)->parameters(['knowledge-sources' => 'slug']);
Route::post('knowledge-sources/{slug}/index', [KnowledgeSourceWebController::class, 'indexDocuments'])->name('knowledge-sources.index-documents');

Route::resource('executions', ExecutionWebController::class)->only(['index', 'show'])->parameters(['executions' => 'id']);
Route::resource('conversations', ConversationWebController::class)->only(['index', 'show'])->parameters(['conversations' => 'id']);

Route::resource('workflows', WorkflowWebController::class)->parameters(['workflows' => 'slug']);
Route::resource('workflow-runs', WorkflowRunWebController::class)->only(['index', 'show'])->parameters(['workflow-runs' => 'uuid']);

Route::resource('widget-settings', WidgetSettingsWebController::class)->only(['index', 'edit', 'update', 'destroy'])->parameters(['widget-settings' => 'agentSlug']);
