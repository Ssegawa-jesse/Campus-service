<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

// Library home page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Reservation pages:
//   GET    /reservations                      reservations.index    list reservations
//   GET    /reservations/create               reservations.create   reservation form
//   POST   /reservations                      reservations.store    save a reservation
//   GET    /reservations/{reservation}/edit   reservations.edit     edit form
//   PUT    /reservations/{reservation}        reservations.update   save changes
//   DELETE /reservations/{reservation}        reservations.destroy  delete a reservation
Route::resource('reservations', ReservationController::class)->except(['show']);
