<?php

use App\Http\Controllers\SentenceController;
use App\Http\Controllers\ScoreController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('App', []);
})->name('app');

Route::group(['prefix' => 'api'], function () {
    Route::get('/sentence', [SentenceController::class, 'getRandom']);
    Route::get('/leaderboard', [ScoreController::class, 'leaderboard']);
});

Route::post('/score', [ScoreController::class, 'store']);
Route::post('/process', [ScoreController::class, 'process']);
