<?php

use App\Http\Controllers\NovedadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('novedades', NovedadController::class)
    ->parameters(['novedades' => 'novedad']);
