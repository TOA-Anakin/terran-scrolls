<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Auth
Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login')
    ->middleware('guest');

Route::get('register', [AuthenticatedSessionController::class, 'register'])
    ->name('register')
    ->middleware('guest');

Route::get('password-reset', [AuthenticatedSessionController::class, 'forgotPassword'])
    ->name('password.reset')
    ->middleware('guest');

Route::post('password-reset-email', [AuthenticatedSessionController::class, 'forgotPasswordMail'])
    ->name('password.reset.email')
    ->middleware('guest');

Route::get('password-reset/{token}', [AuthenticatedSessionController::class, 'forgotPasswordToken'])
    ->name('password.reset.token')
    ->middleware('guest');

Route::post('password-reset-confirm', [AuthenticatedSessionController::class, 'forgotPasswordStore'])
    ->name('password.reset.store')
    ->middleware('guest');

Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->name('login.store')
    ->middleware('guest');

Route::post('register', [AuthenticatedSessionController::class, 'registerStore'])
    ->name('register.store')
    ->middleware('guest');

Route::delete('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');
