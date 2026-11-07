<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/documents/vop', [DocumentController::class, 'vop'])->name('documents.vop');
Route::get('/documents/gdpr', [DocumentController::class, 'gdpr'])->name('documents.gdpr');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::view('/admin/dashboard', 'admin-dashboard')
        ->middleware('can:access-admin-dashboard')
        ->name('admin.dashboard');
    Route::get('/documents/rental-contract', [DocumentController::class, 'rentalContract'])->name('documents.rental');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
