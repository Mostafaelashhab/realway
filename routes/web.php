<?php

use App\Http\Controllers\DevController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SearchController::class, 'home'])->name('home');
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/train', [SearchController::class, 'train'])->name('train');

Route::post('/dev', [DevController::class, 'unlock'])->middleware('throttle:5,1')->name('dev.unlock');
Route::post('/dev/lock', [DevController::class, 'lock'])->name('dev.lock');
