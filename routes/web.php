<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicationDetailController;
use App\Http\Controllers\ApplicationListController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GmailSyncController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('applications', [ApplicationListController::class, 'index'])->name('applications.index');
    Route::get('applications/{application}', [ApplicationDetailController::class, 'show'])->name('applications.show');
    Route::get('applications/{application}/attachments/{attachmentId}', [\App\Http\Controllers\ApplicationAttachmentController::class, 'show'])->name('applications.attachments.show');
    Route::post('applications', [ApplicationController::class, 'store'])->name('applications.store');
    Route::post('gmail/sync', [GmailSyncController::class, 'sync'])->name('gmail.sync');
});

require __DIR__.'/settings.php';
