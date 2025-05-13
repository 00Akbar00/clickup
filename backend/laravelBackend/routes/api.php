<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SignupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Providers\RouteServiceProvider;

use App\Http\Controllers\AuthController;
Route::middleware(['throttle:signup-limiter'])->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/signup', [SignupController::class, 'register']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post("forgot-password",[PasswordResetController::class,'forgotPassword']);
// Route::get('reset-password/{token}', [AuthController::class, 'showResetPasswordLink']);
Route::middleware('check.token.expiry')->group(function () {
    Route::post("reset-password/{token}",[PasswordResetController::class,'resetPassword']);
});