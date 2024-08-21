<?php

use App\Helpers\ResponseHelper;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollageTripController;
use App\Http\Controllers\EnvelopeController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\DestController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\ShipmentTripController;
use App\Http\Controllers\ShipmentRequestController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\ArchiveController;
use App\Models\Day;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
/**
 * Thales
 * start ↓
 */
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->middleware('auth:sanctum');
    Route::post('/verify', [AuthController::class, 'verifyAccount'])
        ->middleware('auth:sanctum');
    Route::get('/request_reset_code', [AuthController::class, 'requestResetPasswordCode'])
        ->middleware('auth:sanctum');
    Route::post('/reset_password', [AuthController::class, 'resetPassword'])
        ->middleware('auth:sanctum');
    Route::post('/store_fcm_token', [AuthController::class, 'storeFcmToken'])
        ->middleware('auth:sanctum');
});
/**
 * Thales
 * end ↑
 */
Route::get('/user/notifications', [\App\Http\Controllers\FcmNotificationController::class, 'index']);
//->middleware('role:User');
Route::prefix('trip')->group(function () {
    Route::post('/add_trip', [TripController::class, 'add_trip'])->middleware('role:Travel Trips Employee,Admin');
    Route::get('/all_trip', [TripController::class, 'all_trip'])->middleware('role:Travel Trips Employee,Admin,User,Driver');
    Route::get('/show_trip_details/{id}', [TripController::class, 'show_trip_details'])->middleware('role:Travel Trips Employee,Admin,Driver,User');
    Route::post('/getTripsByDestinationInArchive', [TripController::class, 'getTripsByDestinationInArchive'])->middleware('role:Travel Trips Employee,Admin');
    Route::get('/getPendingTripsByUser/{userId}', [TripController::class, 'getPendingTripsByUser'])->middleware('role:User');
    Route::get('/getEndingTripsByUser/{userId}', [TripController::class, 'getEndingTripsByUser'])->middleware('role:User');
    Route::get('/getTripsByDriver/{driverId}', [TripController::class, 'getTripsByDriver'])->middleware('role:Driver,Admin');
    Route::put('/endTrip/{id}', [TripController::class, 'endTrip'])->middleware('role:Travel Trips Employee,Admin');
    Route::get('/showArchive', [ArchiveController::class, 'showArchive'])->middleware('role:Travel Trips Employee,Admin');
    Route::delete('/deleteTrip/{id}', [TripController::class, 'deleteTrip'])->middleware('role:Travel Trips Employee,Admin');
    Route::get('/downloadTripOrdersPdf/{id}', [TripController::class, 'downloadTripOrdersPdf'])->middleware('role:Travel Trips Employee,Admin');
    Route::get('/getEndingTripsForDriver/{driverId}', [TripController::class, 'getEndingTripsForDriver'])->middleware('role:Driver,Admin');
});
Route::prefix('destination')->group(function () {
    Route::post('/add_destination', [DestController::class, 'add_destination'])->middleware('role:Travel Trips Employee,Admin,Shipment Employee');
    Route::get('/all_destinations', [DestController::class, 'all_destinations'])->middleware('role:User,Travel Trips Employee,Admin,University trips Employee,Shipment Employee');
});

Route::prefix('bus')->group(function () {
    Route::post('/add_bus', [BusController::class, 'add_bus'])->middleware('role:Admin');
    Route::get('/all_buses', [BusController::class, 'all_buses'])->middleware('role:Travel Trips Employee,Admin,University trips Employee,Shipment Employee');
    Route::delete('/deleteBus/{id}', [BusController::class, 'deleteBus'])->middleware('role:Admin');
    Route::post('/add_imageOfBus', [BusController::class, 'add_imageOfBus'])->middleware('role:Admin');
    Route::get('/allImageOfBus', [BusController::class, 'allImageOfBus'])->middleware('role:Travel Trips Employee,Admin,University trips Employee ,Shipment Employee,User');
});

Route::prefix('reserv')->group(function () {
    Route::post('/creatReservation/{userId}', [ReservationController::class, 'creatReservation'])->middleware('role:User');
    Route::put('/acceptTripRequest/{id}', [ReservationController::class, 'acceptTripRequest'])->middleware('role:Travel Trips Employee,Admin');
    Route::delete('/rejectDeleteTripRequest/{id}', [ReservationController::class, 'rejectDeleteTripRequest'])->middleware('role:Travel Trips Employee,Admin');
    Route::put('/confirmReservation/{id}', [ReservationController::class, 'confirmReservation'])->middleware('role:Travel Trips Employee,Admin');
    Route::get('/getAllReservation', [ReservationController::class, 'getAllReservation'])->middleware('role:Travel Trips Employee,Admin');
    Route::get('/showReservationDetails/{id}', [ReservationController::class, 'showReservationDetails'])->middleware('role:Travel Trips Employee,Admin,User');
    Route::get('/allAcceptedReservations', [ReservationController::class, 'allAcceptedReservations'])->middleware('role:Travel Trips Employee,Admin');
    Route::post('/searchInAllReservation', [ReservationController::class, 'searchInAllReservation'])->middleware('role:Travel Trips Employee,Admin');
    Route::post('/searchInAllAcceptReserv', [ReservationController::class, 'searchInAllAcceptReserv'])->middleware('role:Travel Trips Employee,Admin');
    Route::post('/addPersonFromDash', [ReservationController::class, 'addPersonFromDash'])->middleware('role:Travel Trips Employee,Admin');
    Route::put('/updateReservationFromDash/{id}', [ReservationController::class, 'updateReservationFromDash'])->middleware('role:Travel Trips Employee,Admin');
});

Route::prefix('statistic')->group(function () {
    Route::post('/byDateAndDestenation', [StatisticsController::class, 'byDateAndDestenation'])->middleware('role:Admin');
    Route::get('/tripsCountPerDatePeriod', [StatisticsController::class, 'tripsCountPerDatePeriod'])->middleware('role:Admin');
    Route::get('/tripsCountDestinationPeriod', [StatisticsController::class, 'tripsCountDestinationPeriod'])->middleware('role:Admin');
});

Route::prefix('driver')->group(function () {
    Route::get('/getDrivers', [AuthController::class, 'getDrivers'])->middleware('role:Travel Trips Employee,Admin,University trips Employee,Shipment Employee');
    Route::put('/updateDriver/{id}', [AuthController::class, 'updateDriver'])->middleware('role:Admin');
    Route::delete('/deleteDriver/{id}', [AuthController::class, 'deleteDriver'])->middleware('role:Admin');
});


Route::prefix('user')->group(function () {
    Route::get('/all_Users', [AuthController::class, 'all_Users'])->middleware('role:Admin');
    Route::delete('/delete/{id}', [AuthController::class, 'deleteUser'])->middleware('role:Admin');

    Route::put('/update/{user}', [AuthController::class, 'updateUser'])->middleware('role:Admin,User');
});

/**
 * Thales
 * start ↓
 */
Route::prefix('collage_trips')->group(function () {
    Route::middleware('role:Admin,User,University trips Employee,Driver')->group(function () {
        Route::get('/all', [CollageTripController::class, 'index']);
        Route::get('/details/{id}', [CollageTripController::class, 'show']);
        Route::get('/archived/details/{id}', [CollageTripController::class, 'showArchived']);
        Route::get('/search', [CollageTripController::class, 'searchCollageTrips']);
    });

    Route::middleware('role:Admin,University trips Employee')->group(function () {
        Route::post('/create', [CollageTripController::class, 'create']);
        Route::get('/dailyReservations', [CollageTripController::class, 'dailyReservations']);
        Route::post('/update/{id}', [CollageTripController::class, 'update']);
        Route::delete('/delete/{id}', [CollageTripController::class, 'destroy']);
        Route::post('/acceptSubscription', [SubscriptionController::class, 'update']);
        Route::get('/allSubscription', [SubscriptionController::class, 'index']);
        Route::get('/pendingSubscription', [SubscriptionController::class, 'indexPending']);
    });
    Route::middleware('role:User')->group(function () {
        Route::post('/book', [CollageTripController::class, 'bookDailyCollageTrip']);
        Route::get('/myReservations', [CollageTripController::class, 'userReservations']);
        Route::post('/subscribe', [SubscriptionController::class, 'createNewSubscription']);
        Route::get('/unsubscribe', [SubscriptionController::class, 'cancelSubscription']);
        Route::post('/renew', [SubscriptionController::class, 'renewSubscription']);
        Route::post('/checkCost', [CollageTripController::class, 'checkCost']);
    });
    Route::middleware('role:Admin,Driver,User')->group(function () {
        Route::get('/driverTrips', [CollageTripController::class, 'driverTrips']);
        Route::get('/dailyReservationInfo/{id}', [CollageTripController::class, 'dailyReservationInfo']);
    });
    Route::middleware('role:Admin,Driver')->group(function () {
        Route::get('/payDailyReservation/{id}', [CollageTripController::class, 'payDailyReservation']);
    });
});
Route::prefix('feedback')->group(function () {
    //Route::middleware('role:Admin')->group(function () {
        Route::get('/all', [FeedbackController::class, 'index']);
   // });
    Route::middleware('role:User')->group(function () {
        Route::get('/user', [FeedbackController::class, 'userFeedbacks']);
        Route::post('/create', [FeedbackController::class, 'store']);
    });
    Route::middleware('role:Admin,User')->group(function () {
        Route::get('/show/{id}', [FeedbackController::class, 'show']);
        Route::delete('/delete/{id}', [FeedbackController::class, 'destroy']);
    });
});
Route::prefix('envelop')->group(function () {
    Route::middleware('role:Admin,User,Driver')->group(function () {
        Route::get('/all', [EnvelopeController::class, 'index']); //admin , user , driver
        Route::get('/show/{id}', [EnvelopeController::class, 'show']); //admin , user , driver
    });
    Route::middleware('role:Driver')->group(function () {
        Route::get('/approve', [EnvelopeController::class, 'approve']); //driver
    });
    Route::middleware('role:User')->group(function () {
        Route::post('/create', [EnvelopeController::class, 'store']); //user
    });
});
Route::get('/days', function () {
    $days = Day::all();
    return ResponseHelper::success($days);
});
/**
 * Thales
 * end ↑
 */
Route::prefix('shipmentTrip')->group(function () {
    Route::post('/add_truck', [ShipmentTripController::class, 'add_truck'])->middleware('role:Admin,Shipment Employee');
    Route::delete('/delete_truck/{id}', [ShipmentTripController::class, 'delete_truck'])->middleware('role:Admin,Shipment Employee');
    Route::get('/allTruck', [ShipmentTripController::class, 'allTruck'])->middleware('role:Admin,Shipment Employee');
    Route::post('/add_shipment_trip', [ShipmentTripController::class, 'add_shipment_trip'])->middleware('role:Admin,Shipment Employee');
    Route::put('/endShipmentTrip/{id}', [ShipmentTripController::class, 'endShipmentTrip'])->middleware('role:Admin,Shipment Employee');
    Route::get('/ShowShipmentTripDetails/{id}', [ShipmentTripController::class, 'ShowShipmentTripDetails'])->middleware('role:Admin,Shipment Employee,User');
    Route::get('/allShipmentTrips', [ShipmentTripController::class, 'allShipmentTrips'])->middleware('role:Admin,Shipment Employee,User');
    Route::get('/allPublicShipmentTrips', [ShipmentTripController::class, 'allPublicShipmentTrips'])->middleware('role:Admin,Shipment Employee,User');
    Route::get('/showArchive', [ShipmentTripController::class, 'showArchive'])->middleware('role:Admin,Shipment Employee');
    Route::post('/filterByType', [ShipmentTripController::class, 'filterByType'])->middleware('role:Admin,Shipment Employee');
});

Route::prefix('shipmentRequest')->group(function () {
    Route::post('/addShipmentRequestFromUser', [ShipmentRequestController::class, 'addShipmentRequestFromUser'])->middleware('role:User');
    Route::post('/addShipmentRequestFromDash', [ShipmentRequestController::class, 'addShipmentRequestFromDash'])->middleware('role:Admin,Shipment Employee');
    Route::post('/add_foodstuff', [ShipmentRequestController::class, 'add_foodstuff'])->middleware('role:Admin,Shipment Employee');
    Route::put('/acceptShipmentRequest/{id}', [ShipmentRequestController::class, 'acceptShipmentRequest'])->middleware('role:Admin,Shipment Employee');
    Route::delete('/rejectDeleteShipmentRequest/{id}', [ShipmentRequestController::class, 'rejectDeleteShipmentRequest'])->middleware('role:Admin,Shipment Employee');
    Route::get('/getAllShipmentRequests', [ShipmentRequestController::class, 'getAllShipmentRequests'])->middleware('role:Admin,Shipment Employee');
    Route::get('/getAllAcceptedShipmentRequests', [ShipmentRequestController::class, 'getAllAcceptedShipmentRequests'])->middleware('role:Admin,Shipment Employee');
    Route::get('/AllMyShipmentRequests/{id}', [ShipmentRequestController::class, 'AllMyShipmentRequests'])->middleware('role:User');
    Route::get('/AllMyDoneShipmentRequests/{id}', [ShipmentRequestController::class, 'AllMyDoneShipmentRequests'])->middleware('role:User');
    Route::get('/ShowShipmentRequestDetails/{id}', [ShipmentRequestController::class, 'ShowShipmentRequestDetails'])->middleware('role:Admin,Shipment Employee,User');
    Route::get('/allFoodstuffs', [ShipmentRequestController::class, 'allFoodstuffs'])->middleware('role:Admin,Shipment Employee,User');
});

