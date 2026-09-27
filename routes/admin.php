<?php

use Agentic\Http\Controllers\Admin\AgentController;
use Agentic\Http\Controllers\Admin\ConversationController;
use Agentic\Http\Controllers\Admin\DashboardController;
use Agentic\Http\Controllers\Admin\ExecutionController;
use Agentic\Http\Controllers\Admin\KnowledgeSourceController;
use Agentic\Http\Controllers\Admin\SkillController;
use Agentic\Http\Controllers\Admin\ToolController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::resource('agents', AgentController::class)
    ->parameters(['agents' => 'slug'])
    ->except(['destroy']);
Route::delete('agents/{slug}', [AgentController::class, 'destroy'])->name('agents.destroy');

Route::resource('skills', SkillController::class)
    ->parameters(['skills' => 'slug'])
    ->except(['destroy']);
Route::delete('skills/{slug}', [SkillController::class, 'destroy'])->name('skills.destroy');

Route::resource('tools', ToolController::class)
    ->parameters(['tools' => 'slug'])
    ->except(['destroy']);
Route::delete('tools/{slug}', [ToolController::class, 'destroy'])->name('tools.destroy');

Route::resource('knowledge-sources', KnowledgeSourceController::class)
    ->parameters(['knowledge-sources' => 'slug'])
    ->except(['destroy']);
Route::delete('knowledge-sources/{slug}', [KnowledgeSourceController::class, 'destroy'])
    ->name('knowledge-sources.destroy');
Route::post('knowledge-sources/{slug}/index', [KnowledgeSourceController::class, 'indexDocuments'])
    ->name('knowledge-sources.index-documents');

Route::get('executions', [ExecutionController::class, 'index'])->name('executions.index');
Route::get('executions/{id}', [ExecutionController::class, 'show'])->name('executions.show');

Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
Route::get('conversations/{id}', [ConversationController::class, 'show'])->name('conversations.show');
