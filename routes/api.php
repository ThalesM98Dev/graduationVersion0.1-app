<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\DestController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->middleware('auth:sanctum');
});
Route::prefix('trip')->group(function () {
    Route::post('/add_trip', [TripController::class, 'add_trip']);
    Route::get('/all_trip', [TripController::class, 'all_trip']);
    Route::get('/getTripsByDestination/{destination}', [TripController::class, 'getTripsByDestination']);
    Route::get('/show_trip_details/{id}', [TripController::class, 'show_trip_details']);
    Route::get('/getTripsByUserId/{userId}', [TripController::class, 'getTripsByUserId']);
    Route::get('/getTripsByDriver/{driverId}', [TripController::class, 'getTripsByDriver']);
    Route::put('/endTrip/{id}', [TripController::class, 'endTrip']);
});
Route::prefix('destination')->group(function () {
    Route::post('/add_destination', [DestController::class, 'add_destination']);
    Route::get('/all_destinations', [DestController::class, 'all_destinations']);
    Route::get('/showWithTrips/{id}', [DestController::class, 'showWithTrips']);

});
Route::prefix('orders')->group(function () {
    Route::post('/add_order', [OrderController::class, 'add_order']);
    Route::get('/show_OrdersForUser/{userId}', [OrderController::class, 'show_OrdersForUser']);
    Route::get('/all_orders', [OrderController::class, 'all_orders']);
});

Route::prefix('bus')->group(function () {
    Route::post('/add_bus', [BusController::class, 'add_bus']);
    Route::get('/all_buses', [BusController::class, 'all_buses']);
});

Route::prefix('reserv')->group(function () {
    Route::post('/creat_reservation', [ReservationController::class, 'creat_reservation']);
    Route::put('/acceptTripRequest/{id}', [ReservationController::class, 'acceptTripRequest']);
    Route::delete('/rejectDeleteTripRequest/{id}', [ReservationController::class, 'rejectDeleteTripRequest']);
    Route::put('/confirmReservation/{id}', [ReservationController::class, 'confirmReservation']);
});
