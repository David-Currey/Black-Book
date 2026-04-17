<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('/lists', 'lists.index')->name('lists.index');
    Route::livewire('/lists/{list}', 'lists.show')->name('lists.show');
});

require __DIR__ . '/settings.php';
