<?php

use Agentic\Http\Controllers\Admin\DashboardController;
use Agentic\Http\Controllers\Admin\SkillRoutingSchemaController;
use Agentic\Http\Controllers\Api\SkillController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', DashboardController::class)->name('dashboard');
Route::get('skills/routing-schema', SkillRoutingSchemaController::class)->name('skills.routing-schema');

Route::get('skills', [SkillController::class, 'index'])->name('skills.index');
Route::post('skills', [SkillController::class, 'store'])->name('skills.store');
Route::get('skills/{slug}', [SkillController::class, 'show'])->name('skills.show');
Route::put('skills/{slug}', [SkillController::class, 'update'])->name('skills.update');
Route::delete('skills/{slug}', [SkillController::class, 'destroy'])->name('skills.destroy');
