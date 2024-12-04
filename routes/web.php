<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestDbController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-db', [TestDbController::class, 'testConnection']);
