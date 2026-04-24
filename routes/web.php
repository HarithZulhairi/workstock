<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\AutomotivePartsController;
use App\Http\Controllers\JobOrdersController;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Route::inertia('stocks/index', 'Stocks/Index')->name('displayStock');
    Route::get('stocks/index', [AutomotivePartsController::class, 'index'])->name('displayStock');
    Route::inertia('stocks/create', 'Stocks/Create')->name('createStock');
    Route::post('stocks/store', [AutomotivePartsController::class, 'store'])->name('storeStock');
    Route::post('stocks/add/{id}', [AutomotivePartsController::class, 'addStock'])->name('addStock');
    Route::get('stocks/view/{id}', [AutomotivePartsController::class, 'show'])->name('viewStock');
    Route::get('stocks/edit/{id}', [AutomotivePartsController::class, 'edit'])->name('editStock');
    Route::put('stocks/update/{id}', [AutomotivePartsController::class, 'update'])->name('updateStock');
    Route::post('stocks/delete/{id}', [AutomotivePartsController::class, 'destroy'])->name('destroyStock');

    // JOB ORDERS \\
    Route::get('job-orders/index', [JobOrdersController::class, 'index'])->name('displayJobOrders');
    Route::get('job-orders/create', [JobOrdersController::class, 'create'])->name('createJobOrder');
    Route::post('job-orders/store', [JobOrdersController::class, 'store'])->name('storeJobOrder');
    Route::get('job-orders/edit/{id}', [JobOrdersController::class, 'edit'])->name('editJobOrder');
    Route::put('job-orders/update/{id}', [JobOrdersController::class, 'update'])->name('updateJobOrder');
});

require __DIR__.'/settings.php';
