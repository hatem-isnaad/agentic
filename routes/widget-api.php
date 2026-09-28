<?php

use Agentic\Http\Controllers\Widget\ApprovalController;
use Agentic\Http\Controllers\Widget\AttachmentController;
use Agentic\Http\Controllers\Widget\ConfigController;
use Agentic\Http\Controllers\Widget\ConversationController;
use Agentic\Http\Controllers\Widget\HandoffController;
use Agentic\Http\Controllers\Widget\MessageController;
use Agentic\Http\Controllers\Widget\RealtimeController;
use Illuminate\Support\Facades\Route;

Route::get('config', ConfigController::class)->name('config');

Route::apiResource('conversations', ConversationController::class)->only(['index', 'store']);
Route::get('conversations/{id}/messages', [ConversationController::class, 'messages'])->name('conversations.messages.index');
Route::get('conversations/{id}/files/{file}', [AttachmentController::class, 'show'])
    ->where('file', '[A-Za-z0-9._-]+')
    ->name('conversations.files.show')
    ->withoutMiddleware(['throttle:agentic-widget']);
Route::post('conversations/{id}/messages', [MessageController::class, 'store'])->name('conversations.messages.store');
Route::post('conversations/{id}/handoff', [HandoffController::class, 'store'])->name('conversations.handoff');
Route::get('conversations/{id}/realtime', RealtimeController::class)->name('conversations.realtime');

Route::post('messages', [MessageController::class, 'store'])->name('messages.store');

Route::post('approvals/{id}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
Route::post('approvals/{id}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
Route::post('approvals/{id}/execute', [ApprovalController::class, 'execute'])->name('approvals.execute');
