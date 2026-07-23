<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SearchController::class, 'home'])->name('home');
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/coach/{coachType}', [SearchController::class, 'coach'])->name('coach');
Route::get('/seats', [SearchController::class, 'seats'])->name('seats');
Route::get('/train/{number}', [SearchController::class, 'train'])->name('train');
Route::get('/station/{id}', [SearchController::class, 'station'])->name('station');
Route::get('/route/{from}/{to}', [SearchController::class, 'routePage'])->name('route.page');
Route::view('/wallet', 'wallet')->name('wallet');
Route::view('/stats', 'stats')->name('stats');
Route::view('/offline', 'offline')->name('offline');
