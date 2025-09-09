<?php

use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\WaitlistController;
use Illuminate\Support\Facades\Route;

// Root route now points to registration
Route::get('/', [PublicRegistrationController::class, 'create'])->name('registration.create');

// Redirect old /register route to root for backwards compatibility
Route::get('/register', function () {
    return redirect('/', 301);
});
Route::post('/register', [PublicRegistrationController::class, 'store'])->name('registration.store');
Route::get('/registration/success', [PublicRegistrationController::class, 'success'])->name('registration.success');

// Waitlist Routes
Route::get('/waitlist/join/{token}', [WaitlistController::class, 'showJoinForm'])->name('waitlist.join');
Route::post('/waitlist/join/{token}', [WaitlistController::class, 'joinWaitlist'])->name('waitlist.store');

// Withdrawal Routes
Route::get('/withdraw/{token}', [WaitlistController::class, 'showWithdrawForm'])->name('withdraw.show');
Route::post('/withdraw/{token}', [WaitlistController::class, 'withdraw'])->name('withdraw.store');
Route::get('/withdraw-success', [WaitlistController::class, 'withdrawSuccess'])->name('withdraw.success');

// Status Check Route
Route::get('/status/{token}', [WaitlistController::class, 'status'])->name('waitlist.status');

// Email Preview Routes (for development)
Route::get('/email-preview', [App\Http\Controllers\EmailPreviewController::class, 'index'])->name('email.preview.index');
Route::get('/email-preview/{templateKey}', [App\Http\Controllers\EmailPreviewController::class, 'preview'])->name('email.preview');
