<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SearchController::class, 'home'])->name('home');
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/coach/{coachType}', [SearchController::class, 'coach'])->name('coach');
Route::get('/live-seats', [SearchController::class, 'liveSeats'])->name('live-seats');
