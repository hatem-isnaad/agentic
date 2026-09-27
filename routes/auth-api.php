<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('me', function (Request $request) {
    return response()->json(['data' => ['user' => $request->user()]]);
})->middleware('auth:sanctum')->name('me');

Route::post('token', function (Request $request) {
    $validated = $request->validate([
        'token_name' => ['nullable', 'string', 'max:255'],
    ]);

    $token = $request->user()->createToken(
        $validated['token_name'] ?? config('agentic.auth.sanctum.token_name', 'agentic'),
        config('agentic.auth.sanctum.token_abilities', ['*']),
    );

    return response()->json(['data' => ['token' => $token->plainTextToken]]);
})->middleware('auth:sanctum')->name('token');

Route::post('logout', function (Request $request) {
    if ($request->user()?->currentAccessToken() !== null) {
        $request->user()->currentAccessToken()->delete();
    }

    auth()->guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['data' => ['success' => true]]);
})->middleware('auth:sanctum')->name('logout');

if (class_exists(\Laravel\Passkeys\Http\Controllers\GeneratePasskeyAuthenticationOptionsController::class)) {
    Route::get('passkeys/login/options', \Laravel\Passkeys\Http\Controllers\GeneratePasskeyAuthenticationOptionsController::class)
        ->middleware('guest')
        ->name('passkeys.login.options');

    Route::post('passkeys/login', \Laravel\Passkeys\Http\Controllers\AuthenticateUsingPasskeyController::class)
        ->middleware('guest')
        ->name('passkeys.login');

    Route::get('passkeys/register/options', \Laravel\Passkeys\Http\Controllers\GeneratePasskeyRegistrationOptionsController::class)
        ->middleware('auth:sanctum')
        ->name('passkeys.register.options');

    Route::post('passkeys/register', \Laravel\Passkeys\Http\Controllers\StorePasskeyController::class)
        ->middleware('auth:sanctum')
        ->name('passkeys.register');

    Route::delete('passkeys/{id}', \Laravel\Passkeys\Http\Controllers\DestroyPasskeyController::class)
        ->middleware('auth:sanctum')
        ->name('passkeys.destroy');
}
