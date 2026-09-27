<?php

use Agentic\Http\Controllers\Api\AgentController;
use Agentic\Http\Controllers\Api\AgentExecuteController;
use Agentic\Http\Controllers\Api\AgentRouteController;
use Agentic\Http\Controllers\Api\ConversationController;
use Agentic\Http\Controllers\Api\ExecutionController;
use Agentic\Http\Controllers\Api\KnowledgeSourceController;
use Agentic\Http\Controllers\Api\MemoryController;
use Agentic\Http\Controllers\Api\SkillController;
use Agentic\Http\Controllers\Api\ToolController;
use Illuminate\Support\Facades\Route;

Route::get('agents', [AgentController::class, 'index']);
Route::post('agents', [AgentController::class, 'store']);
Route::get('agents/{slug}', [AgentController::class, 'show']);
Route::put('agents/{slug}', [AgentController::class, 'update']);
Route::delete('agents/{slug}', [AgentController::class, 'destroy']);
Route::post('agents/{slug}/execute', AgentExecuteController::class);
Route::post('route', AgentRouteController::class);
Route::get('executions/{id}', [ExecutionController::class, 'show']);
Route::get('conversations/{id}', [ConversationController::class, 'show']);

Route::get('skills', [SkillController::class, 'index']);
Route::post('skills', [SkillController::class, 'store']);
Route::get('skills/{slug}', [SkillController::class, 'show']);
Route::put('skills/{slug}', [SkillController::class, 'update']);
Route::delete('skills/{slug}', [SkillController::class, 'destroy']);

Route::get('tools', [ToolController::class, 'index']);
Route::post('tools', [ToolController::class, 'store']);
Route::get('tools/{slug}', [ToolController::class, 'show']);
Route::put('tools/{slug}', [ToolController::class, 'update']);
Route::delete('tools/{slug}', [ToolController::class, 'destroy']);

Route::get('knowledge-sources', [KnowledgeSourceController::class, 'index']);
Route::post('knowledge-sources', [KnowledgeSourceController::class, 'store']);
Route::get('knowledge-sources/{slug}', [KnowledgeSourceController::class, 'show']);
Route::put('knowledge-sources/{slug}', [KnowledgeSourceController::class, 'update']);
Route::delete('knowledge-sources/{slug}', [KnowledgeSourceController::class, 'destroy']);
Route::post('knowledge-sources/{slug}/index', [KnowledgeSourceController::class, 'indexDocuments']);

Route::get('memories', [MemoryController::class, 'index']);
Route::post('memories', [MemoryController::class, 'store']);
Route::delete('memories/{id}', [MemoryController::class, 'destroy']);
