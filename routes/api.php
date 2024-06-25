<?php

use App\Helpers\ResponseHelper;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollageTripController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\DestController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\ShipmentTripController;
use App\Http\Controllers\ShipmentRequestController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\ArchiveController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\UserMiddleware;
use App\Models\Day;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return response()->json('Email verified!');
})->middleware(['auth:sanctum', 'signed'])->name('verification.verify');


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->middleware('auth:sanctum');
    Route::post('/verify', [AuthController::class, 'verifyAccount'])
        ->middleware('auth:sanctum');
});
Route::prefix('trip')->group(function () {
    Route::post('/add_trip', [TripController::class, 'add_trip']);
    Route::get('/all_trip', [TripController::class, 'all_trip']);
    Route::post('/getTripsByDestinationInArchive', [TripController::class, 'getTripsByDestinationInArchive']);
    Route::post('/getTripsByDestinationInAllTrips', [TripController::class, 'getTripsByDestinationInAllTrips']);
    Route::get('/show_trip_details/{id}', [TripController::class, 'show_trip_details']);
    Route::get('/getPendingTripsByUser/{userId}', [TripController::class, 'getPendingTripsByUser']);
    Route::get('/getEndingTripsByUser/{userId}', [TripController::class, 'getEndingTripsByUser']);
    Route::get('/getTripsByDriver/{driverId}', [TripController::class, 'getTripsByDriver']);
    Route::put('/endTrip/{id}', [TripController::class, 'endTrip']);
    Route::get('/showArchive', [ArchiveController::class, 'showArchive']);
    Route::delete('/deleteTrip/{id}', [TripController::class, 'deleteTrip']);
    Route::get('/downloadTripOrdersPdf/{id}', [TripController::class, 'downloadTripOrdersPdf']);
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
    Route::delete('/deleteBus/{id}', [BusController::class, 'deleteBus']);
    Route::post('/add_imageOfBus', [BusController::class, 'add_imageOfBus']);
});

Route::prefix('reserv')->group(function () {
    Route::post('/creatReservation/{userId}', [ReservationController::class, 'creatReservation']);
    Route::put('/acceptTripRequest/{id}', [ReservationController::class, 'acceptTripRequest']);
    Route::delete('/rejectDeleteTripRequest/{id}', [ReservationController::class, 'rejectDeleteTripRequest']);
    Route::put('/confirmReservation/{id}', [ReservationController::class, 'confirmReservation']);
    Route::get('/getAllReservation', [ReservationController::class, 'getAllReservation']);
    Route::get('/showReservationDetails/{id}', [ReservationController::class, 'showReservationDetails']);
    Route::get('/allAcceptedReservations', [ReservationController::class, 'allAcceptedReservations']);
    Route::post('/searchInAllReservation', [ReservationController::class, 'searchInAllReservation']);
    Route::post('/searchInAllAcceptReserv', [ReservationController::class, 'searchInAllAcceptReserv']);
    Route::post('/addPersonFromDash', [ReservationController::class, 'addPersonFromDash']);
    Route::put('/updateReservationFromDash/{id}', [ReservationController::class, 'updateReservationFromDash']);
});

Route::prefix('statistic')->group(function () {
    Route::post('/byDateAndDestenation', [StatisticsController::class, 'byDateAndDestenation']);
    Route::get('/tripsCountPerDatePeriod', [StatisticsController::class, 'tripsCountPerDatePeriod']);
    Route::get('/tripsCountDestinationPeriod', [StatisticsController::class, 'tripsCountDestinationPeriod']);
});

Route::prefix('driver')->group(function () {
    Route::get('/getDrivers', [AuthController::class, 'getDrivers']);
    Route::put('/updateDriver/{id}', [AuthController::class, 'updateDriver']);
    Route::delete('/deleteDriver/{id}', [AuthController::class, 'deleteDriver']);
    Route::post('/searchDriver', [AuthController::class, 'searchDriver']);
});
Route::prefix('user')->group(function () {
    Route::get('/all_Users', [AuthController::class, 'all_Users']);
    Route::delete('/delete/{id}', [AuthController::class, 'deleteUser']);

    Route::put('/update/{user}', [AuthController::class, 'updateUser']);
});

Route::prefix('collage_trips')->group(function () {
    Route::get('/all', [CollageTripController::class, 'index']);
    Route::get('/details/{id}', [CollageTripController::class, 'show']);
    Route::post('/create', [CollageTripController::class, 'create']);
    Route::post('/book', [CollageTripController::class, 'bookDailyCollageTrip']);
    Route::get('/dailyReservations', [CollageTripController::class, 'dailyReservations']); //
    Route::get('/myReservations', [CollageTripController::class, 'userReservations']); //
    Route::get('/search', [CollageTripController::class, 'searchCollageTrips']);
    Route::post('/update/{id}', [CollageTripController::class, 'update']);
    Route::delete('/delete/{id}', [CollageTripController::class, 'destroy']);
    Route::post('/subscribe', [SubscriptionController::class, 'createNewSubscription']);
    Route::get('/unsubscribe', [SubscriptionController::class, 'cancelSubscription']);
    Route::post('/renew', [SubscriptionController::class, 'renewSubscription']);
    Route::post('/acceptSubscription', [SubscriptionController::class, 'update']);
    Route::post('/payDailyReservation', [CollageTripController::class, 'payDailyReservation']);
    Route::get('/allSubscription', [SubscriptionController::class, 'index']);
    Route::get('/pendingSubscription', [SubscriptionController::class, 'indexPending']);

    Route::get('/driverTrips', [CollageTripController::class, 'driverTrips']); //

    Route::post('/checkCost', [CollageTripController::class, 'checkCost']);
});


Route::prefix('feedback')->group(function () {
    Route::get('/all', [FeedbackController::class, 'index'])->middleware('role:admin');
    Route::get('/user', [FeedbackController::class, 'userFeedbacks'])->middleware('role:user');
    Route::get('/show/{id}', [FeedbackController::class, 'show'])->middleware('role:Admin,User'); //Role:Admin,User,Driver,Shipment Employee,Travel Trips Employee,University trips Employee
    Route::post('/create', [FeedbackController::class, 'store'])->middleware('role:user');
    Route::delete('/delete/{id}', [FeedbackController::class, 'destroy'])->middleware('role:admin');
});

Route::get('/days', function () {
    $days = Day::all();
    return ResponseHelper::success($days);
});

Route::prefix('shipmentTrip')->group(function () {
    Route::post('/add_truck', [ShipmentTripController::class, 'add_truck']);
    Route::delete('/delete_truck/{id}', [ShipmentTripController::class, 'delete_truck']);
    Route::get('/allTruck', [ShipmentTripController::class, 'allTruck']);
    Route::post('/add_shipment_trip', [ShipmentTripController::class, 'add_shipment_trip']);
    Route::put('/endShipmentTrip/{id}', [ShipmentTripController::class, 'endShipmentTrip']);
    Route::get('/ShowShipmentTripDetails/{id}', [ShipmentTripController::class, 'ShowShipmentTripDetails']);
    Route::get('/allShipmentTrips', [ShipmentTripController::class, 'allShipmentTrips']);
    Route::get('/allPublicShipmentTrips', [ShipmentTripController::class, 'allPublicShipmentTrips']);
    Route::get('/showArchive', [ShipmentTripController::class, 'showArchive']);
    Route::post('/filterByType', [ShipmentTripController::class, 'filterByType']);
});

Route::prefix('shipmentRequest')->group(function () {
    Route::post('/add_shipment_request', [ShipmentRequestController::class, 'add_shipment_request']);
    Route::post('/add_foodstuff', [ShipmentRequestController::class, 'add_foodstuff']);
    Route::put('/acceptShipmentRequest/{id}', [ShipmentRequestController::class, 'acceptShipmentRequest']);
    Route::delete('/rejectDeleteShipmentRequest/{id}', [ShipmentRequestController::class, 'rejectDeleteShipmentRequest']);
    Route::get('/getAllShipmentRequests', [ShipmentRequestController::class, 'getAllShipmentRequests']);
    Route::get('/getAllAcceptedShipmentRequests', [ShipmentRequestController::class, 'getAllAcceptedShipmentRequests']);
    Route::get('/AllMyShipmentRequests/{id}', [ShipmentRequestController::class, 'AllMyShipmentRequests']);
    Route::get('/AllMyDoneShipmentRequests/{id}', [ShipmentRequestController::class, 'AllMyDoneShipmentRequests']);
    Route::get('/ShowShipmentRequestDetails/{id}', [ShipmentRequestController::class, 'ShowShipmentRequestDetails']);
    Route::get('/allFoodstuffs', [ShipmentRequestController::class, 'allFoodstuffs']);
});
