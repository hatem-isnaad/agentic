<?php

use Agentic\Http\Controllers\Channels\MessengerMetaWebhookController;
use Agentic\Http\Controllers\Channels\WhatsAppMetaWebhookController;
use Agentic\Http\Controllers\Channels\WhatsAppWebJsWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('whatsapp/meta', [WhatsAppMetaWebhookController::class, 'verify'])->name('whatsapp.meta.verify');
Route::post('whatsapp/meta', [WhatsAppMetaWebhookController::class, 'receive'])->name('whatsapp.meta.receive');
Route::post('whatsapp/webjs', [WhatsAppWebJsWebhookController::class, 'receive'])->name('whatsapp.webjs.receive');
Route::get('messenger/meta', [MessengerMetaWebhookController::class, 'verify'])->name('messenger.meta.verify');
Route::post('messenger/meta', [MessengerMetaWebhookController::class, 'receive'])->name('messenger.meta.receive');
