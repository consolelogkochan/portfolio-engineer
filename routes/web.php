<?php

use App\Http\Controllers\WorkController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/works', [WorkController::class, 'index']);
Route::get('/works/{slug}', [WorkController::class, 'show']);
