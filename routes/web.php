<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\WorkController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/works', [WorkController::class, 'index']);
Route::get('/works/{slug}', [WorkController::class, 'show']);

Route::get('/logs', [LogController::class, 'index']);
Route::get('/logs/{slug}', [LogController::class, 'show']);

Route::get('/contact', [ContactController::class, 'create']);
Route::post('/contact', [ContactController::class, 'store']);
