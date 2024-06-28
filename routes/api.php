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
    Route::post('/add_trip', [TripController::class, 'add_trip']);//->middleware('role:Travel Trips Employee , Admin')
    Route::get('/all_trip', [TripController::class, 'all_trip']);//->middleware('role:Travel Trips Employee , Admin ,Use')
    Route::get('/show_trip_details/{id}', [TripController::class, 'show_trip_details']);//->middleware('role:Travel Trips Employee , Admin , Driver')
    Route::get('/getPendingTripsByUser/{userId}', [TripController::class, 'getPendingTripsByUser']);//->middleware('role:User')
    Route::get('/getEndingTripsByUser/{userId}', [TripController::class, 'getEndingTripsByUser']);//->middleware('role:User')
    Route::get('/getTripsByDriver/{driverId}', [TripController::class, 'getTripsByDriver']);//->middleware('role:Driver , Admin')
    Route::put('/endTrip/{id}', [TripController::class, 'endTrip']);//->middleware('role:Travel Trips Employee , Admin')
    Route::get('/showArchive', [ArchiveController::class, 'showArchive']);//->middleware('role:Travel Trips Employee , Admin')
    Route::delete('/deleteTrip/{id}', [TripController::class, 'deleteTrip']);//->middleware('role:Travel Trips Employee , Admin')
    Route::get('/downloadTripOrdersPdf/{id}', [TripController::class, 'downloadTripOrdersPdf']);//->middleware('role:Travel Trips Employee , Admin')
    Route::get('/getEndingTripsForDriver/{driverId}', [TripController::class, 'getEndingTripsForDriver']);//->middleware('role:Driver , Admin');
});
Route::prefix('destination')->group(function () {
    Route::post('/add_destination', [DestController::class, 'add_destination']);//->middleware('role:Travel Trips Employee , Admin')
    Route::get('/all_destinations', [DestController::class, 'all_destinations']);//->middleware('role:Travel Trips Employee , Admin , University trips Employee , Shipment Employee')
});

Route::prefix('bus')->group(function () {
    Route::post('/add_bus', [BusController::class, 'add_bus']);//->middleware('role:Admin')
    Route::get('/all_buses', [BusController::class, 'all_buses']);//->middleware('role:Travel Trips Employee , Admin , University trips Employee , Shipment Employee')
    Route::delete('/deleteBus/{id}', [BusController::class, 'deleteBus']);//->middleware('role:Admin')
    Route::post('/add_imageOfBus', [BusController::class, 'add_imageOfBus']);//->middleware('role:Admin')
    Route::get('/allImageOfBus', [BusController::class, 'allImageOfBus']);//->middleware('role:Travel Trips Employee , Admin , University trips Employee , Shipment Employee , User')
});

Route::prefix('reserv')->group(function () {
    Route::post('/creatReservation/{userId}', [ReservationController::class, 'creatReservation']);//->middleware('role:User');
    Route::put('/acceptTripRequest/{id}', [ReservationController::class, 'acceptTripRequest']);//->middleware('role:Travel Trips Employee , Admin');
    Route::delete('/rejectDeleteTripRequest/{id}', [ReservationController::class, 'rejectDeleteTripRequest']);//->middleware('role:Travel Trips Employee , Admin');
    Route::put('/confirmReservation/{id}', [ReservationController::class, 'confirmReservation']);//->middleware('role:Travel Trips Employee , Admin');
    Route::get('/getAllReservation', [ReservationController::class, 'getAllReservation']);//->middleware('role:Travel Trips Employee , Admin');
    Route::get('/showReservationDetails/{id}', [ReservationController::class, 'showReservationDetails']);//->middleware('role:Travel Trips Employee , Admin');
    Route::get('/allAcceptedReservations', [ReservationController::class, 'allAcceptedReservations']);//->middleware('role:Travel Trips Employee , Admin');
    Route::post('/searchInAllReservation', [ReservationController::class, 'searchInAllReservation']);//->middleware('role:Travel Trips Employee , Admin');
    Route::post('/searchInAllAcceptReserv', [ReservationController::class, 'searchInAllAcceptReserv']);//->middleware('role:Travel Trips Employee , Admin');
    Route::post('/addPersonFromDash', [ReservationController::class, 'addPersonFromDash']);//->middleware('role:Travel Trips Employee , Admin');
    Route::put('/updateReservationFromDash/{id}', [ReservationController::class, 'updateReservationFromDash']);//->middleware('role:Travel Trips Employee , Admin');
});

Route::prefix('statistic')->group(function () {
    Route::post('/byDateAndDestenation', [StatisticsController::class, 'byDateAndDestenation']);//->middleware('role:Admin');
    Route::get('/tripsCountPerDatePeriod', [StatisticsController::class, 'tripsCountPerDatePeriod']);
    Route::get('/tripsCountDestinationPeriod', [StatisticsController::class, 'tripsCountDestinationPeriod']);
});

Route::prefix('driver')->group(function () {
    Route::get('/getDrivers', [AuthController::class, 'getDrivers']);//->middleware('role:Travel Trips Employee , Admin , University trips Employee , Shipment Employee');
    Route::put('/updateDriver/{id}', [AuthController::class, 'updateDriver']);//->middleware('role:Admin');
    Route::delete('/deleteDriver/{id}', [AuthController::class, 'deleteDriver']);//->middleware('role:Admin');
});
Route::prefix('user')->group(function () {
    Route::get('/all_Users', [AuthController::class, 'all_Users']);//->middleware('role:Admin');
    Route::delete('/delete/{id}', [AuthController::class, 'deleteUser']);//->middleware('role:Admin');

    Route::put('/update/{user}', [AuthController::class, 'updateUser']);//->middleware('role:Admin');
});

Route::prefix('collage_trips')->group(function () {
    Route::middleware('role:Admin,User,University trips Employee')->group(function () {
        Route::get('/all', [CollageTripController::class, 'index'])->name('collage_trips.index');
        Route::get('/details/{id}', [CollageTripController::class, 'show'])->name('collage_trips.show');
        Route::get('/search', [CollageTripController::class, 'searchCollageTrips'])->name('collage_trips.search');
    });

    Route::middleware('role:Admin,University trips Employee')->group(function () {
        Route::post('/create', [CollageTripController::class, 'create'])->name('collage_trips.create');
        Route::get('/dailyReservations', [CollageTripController::class, 'dailyReservations'])->name('collage_trips.dailyReservations');
        Route::post('/update/{id}', [CollageTripController::class, 'update'])->name('collage_trips.update');
        Route::delete('/delete/{id}', [CollageTripController::class, 'destroy'])->name('collage_trips.destroy');
        Route::post('/acceptSubscription', [SubscriptionController::class, 'update'])->name('subscription.accept');
        Route::get('/allSubscription', [SubscriptionController::class, 'index'])->name('subscription.index');
        Route::get('/pendingSubscription', [SubscriptionController::class, 'indexPending'])->name('subscription.pending');
    });

    Route::middleware('role:User')->group(function () {
        Route::post('/book', [CollageTripController::class, 'bookDailyCollageTrip'])->name('collage_trips.book');
        Route::get('/myReservations', [CollageTripController::class, 'userReservations'])->name('collage_trips.myReservations');
        Route::post('/subscribe', [SubscriptionController::class, 'createNewSubscription'])->name('subscription.create');
        Route::get('/unsubscribe', [SubscriptionController::class, 'cancelSubscription'])->name('subscription.cancel');
        Route::post('/renew', [SubscriptionController::class, 'renewSubscription'])->name('subscription.renew');
        Route::post('/payDailyReservation', [CollageTripController::class, 'payDailyReservation'])->name('collage_trips.pay');
        Route::post('/checkCost', [CollageTripController::class, 'checkCost'])->name('collage_trips.checkCost');
    });

    Route::middleware('role:Driver')->group(function () {
        Route::get('/driverTrips', [CollageTripController::class, 'driverTrips'])->name('collage_trips.driverTrips');
    });
});


Route::prefix('feedback')->group(function () {
    Route::get('/all', [FeedbackController::class, 'index']);
    Route::get('/user', [FeedbackController::class, 'userFeedbacks']);
    Route::get('/show/{id}', [FeedbackController::class, 'show']);
    //->middleware('role:Admin,User'); //Role:Admin,User,Driver,Shipment Employee,Travel Trips Employee,University trips Employee
    Route::post('/create', [FeedbackController::class, 'store']);
    Route::delete('/delete/{id}', [FeedbackController::class, 'destroy']);
});

Route::get('/days', function () {
    $days = Day::all();
    return ResponseHelper::success($days);
});

Route::prefix('shipmentTrip')->group(function () {
    Route::post('/add_truck', [ShipmentTripController::class, 'add_truck']);//->middleware('role:Admin,Shipment Employee');
    Route::delete('/delete_truck/{id}', [ShipmentTripController::class, 'delete_truck']);//->middleware('role:Admin,Shipment Employee');
    Route::get('/allTruck', [ShipmentTripController::class, 'allTruck']);//->middleware('role:Admin,Shipment Employee');
    Route::post('/add_shipment_trip', [ShipmentTripController::class, 'add_shipment_trip']);//->middleware('role:Admin,Shipment Employee');
    Route::put('/endShipmentTrip/{id}', [ShipmentTripController::class, 'endShipmentTrip']);//->middleware('role:Admin,Shipment Employee');
    Route::get('/ShowShipmentTripDetails/{id}', [ShipmentTripController::class, 'ShowShipmentTripDetails']);//->middleware('role:Admin,Shipment Employee');
    Route::get('/allShipmentTrips', [ShipmentTripController::class, 'allShipmentTrips']);
    Route::get('/allPublicShipmentTrips', [ShipmentTripController::class, 'allPublicShipmentTrips']);
    Route::get('/showArchive', [ShipmentTripController::class, 'showArchive']);//->middleware('role:Admin,Shipment Employee');
    Route::post('/filterByType', [ShipmentTripController::class, 'filterByType']);//->middleware('role:Admin,Shipment Employee');
});

Route::prefix('shipmentRequest')->group(function () {
    Route::post('/addShipmentRequestFromUser', [ShipmentRequestController::class, 'addShipmentRequestFromUser']);//->middleware('role:User');
    Route::post('/addShipmentRequestFromDash', [ShipmentRequestController::class, 'addShipmentRequestFromDash']);//->middleware('role:Admin , Shipment Employee');
    Route::post('/add_foodstuff', [ShipmentRequestController::class, 'add_foodstuff']);//->middleware('role:Admin , Shipment Employee');
    Route::put('/acceptShipmentRequest/{id}', [ShipmentRequestController::class, 'acceptShipmentRequest']);//->middleware('role:Admin , Shipment Employee');
    Route::delete('/rejectDeleteShipmentRequest/{id}', [ShipmentRequestController::class, 'rejectDeleteShipmentRequest']);//->middleware('role:Admin , Shipment Employee');
    Route::get('/getAllShipmentRequests', [ShipmentRequestController::class, 'getAllShipmentRequests']);//->middleware('role:Admin , Shipment Employee');
    Route::get('/getAllAcceptedShipmentRequests', [ShipmentRequestController::class, 'getAllAcceptedShipmentRequests']);//->middleware('role:Admin , Shipment Employee');
    Route::get('/AllMyShipmentRequests/{id}', [ShipmentRequestController::class, 'AllMyShipmentRequests']);//->middleware('role:User');
    Route::get('/AllMyDoneShipmentRequests/{id}', [ShipmentRequestController::class, 'AllMyDoneShipmentRequests']);//->middleware('role:User');
    Route::get('/ShowShipmentRequestDetails/{id}', [ShipmentRequestController::class, 'ShowShipmentRequestDetails']);//->middleware('role:Admin , Shipment Employee');
    Route::get('/allFoodstuffs', [ShipmentRequestController::class, 'allFoodstuffs']);//->middleware('role:Admin , Shipment Employee , User');
});
