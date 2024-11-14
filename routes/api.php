<?php

use App\Http\Controllers\UserReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource('reservations', UserReservationController::class)
    ->middleware('auth:sanctum');