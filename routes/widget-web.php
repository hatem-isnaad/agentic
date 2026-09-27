<?php

use Agentic\Http\Controllers\Widget\Web\ChatWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', ChatWebController::class)->name('chat');
