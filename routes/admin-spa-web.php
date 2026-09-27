<?php

use Agentic\Http\Controllers\Admin\Web\AdminSpaController;
use Illuminate\Support\Facades\Route;

Route::get('/{path?}', AdminSpaController::class)
    ->where('path', '.*')
    ->name('spa');
