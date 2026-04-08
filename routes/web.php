<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\AutomotivePartsController;

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
});

require __DIR__.'/settings.php';
