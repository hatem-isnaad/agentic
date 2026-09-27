<?php

use Agentic\Http\Controllers\Api\AgentController;
use Agentic\Http\Controllers\Api\AgentExecuteController;
use Agentic\Http\Controllers\Api\AgentRouteController;
use Agentic\Http\Controllers\Api\ConversationController;
use Agentic\Http\Controllers\Api\ExecutionController;
use Illuminate\Support\Facades\Route;

Route::get('agents', [AgentController::class, 'index']);
Route::get('agents/{slug}', [AgentController::class, 'show']);
Route::post('agents/{slug}/execute', AgentExecuteController::class);
Route::post('route', AgentRouteController::class);
Route::get('executions/{id}', [ExecutionController::class, 'show']);
Route::get('conversations/{id}', [ConversationController::class, 'show']);
