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

Route::get('agents', [AgentWebController::class, 'index'])->name('agents.index');
Route::get('agents/create', [AgentWebController::class, 'create'])->name('agents.create');
Route::post('agents', [AgentWebController::class, 'store'])->name('agents.store');
Route::get('agents/{slug}', [AgentWebController::class, 'show'])->name('agents.show');
Route::get('agents/{slug}/edit', [AgentWebController::class, 'edit'])->name('agents.edit');
Route::put('agents/{slug}', [AgentWebController::class, 'update'])->name('agents.update');
Route::delete('agents/{slug}', [AgentWebController::class, 'destroy'])->name('agents.destroy');

Route::get('skills', [SkillWebController::class, 'index'])->name('skills.index');
Route::get('skills/create', [SkillWebController::class, 'create'])->name('skills.create');
Route::post('skills', [SkillWebController::class, 'store'])->name('skills.store');
Route::get('skills/{slug}', [SkillWebController::class, 'show'])->name('skills.show');
Route::get('skills/{slug}/edit', [SkillWebController::class, 'edit'])->name('skills.edit');
Route::put('skills/{slug}', [SkillWebController::class, 'update'])->name('skills.update');
Route::delete('skills/{slug}', [SkillWebController::class, 'destroy'])->name('skills.destroy');

Route::get('tools', [ToolWebController::class, 'index'])->name('tools.index');
Route::get('tools/create', [ToolWebController::class, 'create'])->name('tools.create');
Route::post('tools', [ToolWebController::class, 'store'])->name('tools.store');
Route::get('tools/{slug}', [ToolWebController::class, 'show'])->name('tools.show');
Route::get('tools/{slug}/edit', [ToolWebController::class, 'edit'])->name('tools.edit');
Route::put('tools/{slug}', [ToolWebController::class, 'update'])->name('tools.update');
Route::delete('tools/{slug}', [ToolWebController::class, 'destroy'])->name('tools.destroy');

Route::get('knowledge-sources', [KnowledgeSourceWebController::class, 'index'])->name('knowledge-sources.index');
Route::get('knowledge-sources/create', [KnowledgeSourceWebController::class, 'create'])->name('knowledge-sources.create');
Route::post('knowledge-sources', [KnowledgeSourceWebController::class, 'store'])->name('knowledge-sources.store');
Route::get('knowledge-sources/{slug}', [KnowledgeSourceWebController::class, 'show'])->name('knowledge-sources.show');
Route::get('knowledge-sources/{slug}/edit', [KnowledgeSourceWebController::class, 'edit'])->name('knowledge-sources.edit');
Route::put('knowledge-sources/{slug}', [KnowledgeSourceWebController::class, 'update'])->name('knowledge-sources.update');
Route::delete('knowledge-sources/{slug}', [KnowledgeSourceWebController::class, 'destroy'])->name('knowledge-sources.destroy');
Route::post('knowledge-sources/{slug}/index', [KnowledgeSourceWebController::class, 'indexDocuments'])->name('knowledge-sources.index-documents');

Route::get('executions', [ExecutionWebController::class, 'index'])->name('executions.index');
Route::get('executions/{id}', [ExecutionWebController::class, 'show'])->name('executions.show');

Route::get('conversations', [ConversationWebController::class, 'index'])->name('conversations.index');
Route::get('conversations/{id}', [ConversationWebController::class, 'show'])->name('conversations.show');

Route::get('workflows', [WorkflowWebController::class, 'index'])->name('workflows.index');
Route::get('workflows/create', [WorkflowWebController::class, 'create'])->name('workflows.create');
Route::post('workflows', [WorkflowWebController::class, 'store'])->name('workflows.store');
Route::get('workflows/{slug}', [WorkflowWebController::class, 'show'])->name('workflows.show');
Route::get('workflows/{slug}/edit', [WorkflowWebController::class, 'edit'])->name('workflows.edit');
Route::put('workflows/{slug}', [WorkflowWebController::class, 'update'])->name('workflows.update');
Route::delete('workflows/{slug}', [WorkflowWebController::class, 'destroy'])->name('workflows.destroy');

Route::get('workflow-runs', [WorkflowRunWebController::class, 'index'])->name('workflow-runs.index');
Route::get('workflow-runs/{uuid}', [WorkflowRunWebController::class, 'show'])->name('workflow-runs.show');

Route::get('widget-settings', [WidgetSettingsWebController::class, 'index'])->name('widget-settings.index');
Route::get('widget-settings/{agentSlug}/edit', [WidgetSettingsWebController::class, 'edit'])->name('widget-settings.edit');
Route::put('widget-settings/{agentSlug}', [WidgetSettingsWebController::class, 'update'])->name('widget-settings.update');
Route::delete('widget-settings/{agentSlug}', [WidgetSettingsWebController::class, 'destroy'])->name('widget-settings.destroy');
