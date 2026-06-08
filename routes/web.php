<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ListTransferController;

Route::view('/', 'welcome')->name('home');

// Authentication routes 
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('/search', 'search.index')->name('search.index');
    Route::livewire('/lists', 'lists.index')->name('lists.index');
    Route::livewire('/lists/{list}', 'lists.show')->name('lists.show');
    Route::livewire('/people/{person}', 'people.show')->name('people.show');

    Route::get('/lists/{list}/export', [ListTransferController::class, 'export'])
        ->name('lists.export');

    Route::post('/lists/import', [ListTransferController::class, 'import'])
        ->name('lists.import');
});

require __DIR__ . '/settings.php';
