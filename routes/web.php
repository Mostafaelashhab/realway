<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SearchController::class, 'home'])->name('home');
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/train', [SearchController::class, 'train'])->name('train');
