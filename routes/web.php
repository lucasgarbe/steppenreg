<?php

use App\Http\Controllers\PublicRegistrationController;
use Illuminate\Support\Facades\Route;

// Root route now points to registration
Route::get('/', [PublicRegistrationController::class, 'create'])->name('registration.create');

// Redirect old /register route to root for backwards compatibility
Route::get('/register', function () {
    return redirect('/', 301);
});
Route::post('/register', [PublicRegistrationController::class, 'store'])->name('registration.store');
Route::get('/registration/success', [PublicRegistrationController::class, 'success'])->name('registration.success');

// Email Preview Routes (for development)
Route::get('/email-preview', [App\Http\Controllers\EmailPreviewController::class, 'index'])->name('email.preview.index');
Route::get('/email-preview/{templateKey}', [App\Http\Controllers\EmailPreviewController::class, 'preview'])->name('email.preview');
