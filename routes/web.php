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
    Route::inertia('stocks/add', 'Stocks/Create')->name('createStock');
    Route::post('stocks/add', [AutomotivePartsController::class, 'store'])->name('storeStock');
    Route::get('stocks/view/{id}', [AutomotivePartsController::class, 'show'])->name('viewStock');
});

require __DIR__.'/settings.php';
