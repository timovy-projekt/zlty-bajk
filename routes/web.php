<?php

use App\Http\Controllers\Admin\BikeController;
use App\Http\Controllers\Admin\RentalApplicationController as AdminRentalApplicationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalApplicationController;
use App\Models\RentalApplication;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/documents/vop', [DocumentController::class, 'vop'])->name('documents.vop');
Route::get('/documents/gdpr', [DocumentController::class, 'gdpr'])->name('documents.gdpr');

Route::middleware(['auth', 'active', 'verified'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::view('/admin/dashboard', 'admin-dashboard')
        ->middleware('can:access-admin-dashboard')
        ->name('admin.dashboard');
    Route::get('/admin/bikes', [BikeController::class, 'index'])
        ->middleware('can:access-admin-dashboard')
        ->name('admin.bikes.index');
    Route::get('/documents/rental-contract', [DocumentController::class, 'rentalContract'])->name('documents.rental');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('api/v1')->name('api.v1.')
    ->middleware(['auth', 'active', 'can:viewAny,'.RentalApplication::class])
    ->group(function (): void {
        Route::middleware('can:access-customer-dashboard')->group(function (): void {
            Route::get('/rental-applications', [RentalApplicationController::class, 'index'])
                ->name('rental-applications.index');
            Route::get('/rental-applications/{rentalApplication}', [RentalApplicationController::class, 'show'])
                ->whereNumber('rentalApplication')
                ->name('rental-applications.show');
        });

        Route::prefix('admin')->name('admin.')->middleware('can:access-admin-dashboard')->group(function (): void {
            Route::get('/rental-applications', [AdminRentalApplicationController::class, 'index'])
                ->name('rental-applications.index');
            Route::get('/rental-applications/{rentalApplication}', [AdminRentalApplicationController::class, 'show'])
                ->whereNumber('rentalApplication')
                ->name('rental-applications.show');
        });
    });

require __DIR__.'/auth.php';
