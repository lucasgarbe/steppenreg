<?php

use App\Http\Controllers\PublicRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public Registration Routes
Route::get('/register', [PublicRegistrationController::class, 'create'])->name('registration.create');
Route::post('/register', [PublicRegistrationController::class, 'store'])->name('registration.store');
Route::get('/registration/success', [PublicRegistrationController::class, 'success'])->name('registration.success');
