<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/reset-password/{token}', function (Request $request, string $token) {
    $frontendUrl = (string) config('app.frontend_url');
    $frontendUrl = rtrim($frontendUrl, '/');

    $email = $request->query('email');

    $query = http_build_query(array_filter([
        'token' => $token,
        'email' => $email,
    ], static fn ($value): bool => $value !== null && $value !== ''));

    return redirect()->away($frontendUrl.'/reset-password?'.$query);
})->name('password.reset');
