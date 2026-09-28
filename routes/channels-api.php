<?php

use Agentic\Http\Controllers\Channels\WhatsAppMetaWebhookController;
use Agentic\Http\Controllers\Channels\WhatsAppWebJsWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('whatsapp/meta', [WhatsAppMetaWebhookController::class, 'verify'])->name('whatsapp.meta.verify');
Route::post('whatsapp/meta', [WhatsAppMetaWebhookController::class, 'receive'])->name('whatsapp.meta.receive');
Route::post('whatsapp/webjs', [WhatsAppWebJsWebhookController::class, 'receive'])->name('whatsapp.webjs.receive');
