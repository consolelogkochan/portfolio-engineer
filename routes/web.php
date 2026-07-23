<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\WorkController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);

Route::get('/works', [WorkController::class, 'index']);
Route::get('/works/{slug}', [WorkController::class, 'show']);

Route::get('/logs/{slug}', [LogController::class, 'show']);

Route::get('/about', AboutController::class);

Route::get('/contact', [ContactController::class, 'create']);
// throttle:3,1 = 1分間に3回まで（頻度ベース。中身ベースのスパム判定は ContactRequest が担う）
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:3,1');
