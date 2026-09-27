<?php

use Agentic\Http\Controllers\Admin\DashboardController;
use Agentic\Http\Controllers\Admin\SkillRoutingSchemaController;
use Agentic\Http\Controllers\Api\AgentController;
use Agentic\Http\Controllers\Api\KnowledgeSourceController;
use Agentic\Http\Controllers\Api\SkillController;
use Agentic\Http\Controllers\Api\ToolController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', DashboardController::class)->name('dashboard');
Route::get('skills/routing-schema', SkillRoutingSchemaController::class)->name('skills.routing-schema');

Route::get('agents', [AgentController::class, 'index'])->name('agents.index');
Route::post('agents', [AgentController::class, 'store'])->name('agents.store');
Route::get('agents/{slug}', [AgentController::class, 'show'])->name('agents.show');
Route::put('agents/{slug}', [AgentController::class, 'update'])->name('agents.update');
Route::delete('agents/{slug}', [AgentController::class, 'destroy'])->name('agents.destroy');

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

Route::get('knowledge-sources', [KnowledgeSourceController::class, 'index'])->name('knowledge-sources.index');
Route::post('knowledge-sources', [KnowledgeSourceController::class, 'store'])->name('knowledge-sources.store');
Route::get('knowledge-sources/{slug}', [KnowledgeSourceController::class, 'show'])->name('knowledge-sources.show');
Route::put('knowledge-sources/{slug}', [KnowledgeSourceController::class, 'update'])->name('knowledge-sources.update');
Route::delete('knowledge-sources/{slug}', [KnowledgeSourceController::class, 'destroy'])->name('knowledge-sources.destroy');
Route::post('knowledge-sources/{slug}/index', [KnowledgeSourceController::class, 'indexDocuments'])->name('knowledge-sources.index-documents');
