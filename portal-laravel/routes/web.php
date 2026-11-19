<?php

use App\Http\Controllers\InicioController;
use App\Http\Controllers\NovedadController;
use Illuminate\Support\Facades\Route;

Route::get('/', InicioController::class)->name('inicio');
Route::view('/institucion', 'institucion')->name('institucion');
Route::view('/recursos', 'recursos')->name('recursos');

Route::resource('novedades', NovedadController::class)
    ->parameters(['novedades' => 'novedad']);
