<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\CheckCostRequest;
use App\Http\Requests\CreateCollageTripRequest;
use App\Http\Requests\CreateDailyReservationRequest;
use App\Http\Requests\PayRequest;
use App\Http\Requests\UpdateCollageTripRequest;
use App\Models\CollageTrip;
use App\Models\DailyCollageReservation;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollageTripController extends Controller
{
    public $tripService;

    public function __construct(TripService $tripService)
    {
        $this->tripService = $tripService;
    }

    public function index(Request $request)
    {
        $result = $this->tripService->listCollageTrips($request);
        return ResponseHelper::success($result);
    }

    public function show($trip_id, Request $request)
    {
        if ('mobile' == $request->type) {
            $result = $this->tripService->collageTripDetailsMobile($trip_id);
        } else {
            $result = $this->tripService->collageTripDetails($trip_id);
        }
        return ResponseHelper::success($result);
    }

    public function create(CreateCollageTripRequest $request): JsonResponse
    {
        $result = $this->tripService->createCollageTrip($request);
        return ResponseHelper::success($result, 'Created successfully.');
    }

    public function update($tripId, UpdateCollageTripRequest $request): JsonResponse
    {
        $result = $this->tripService->updateCollageTrip($tripId, $request);
        return ResponseHelper::success($result, 'Updated successfully');
    }

    public function destroy($trip_id)
    {
        $result = $this->tripService->deleteCollageTrip($trip_id);
        return ResponseHelper::success($result, 'Deleted successfully');
    }

    public function bookDailyCollageTrip(CreateDailyReservationRequest $request)
    {
        $result = $this->tripService->bookDailyCollageTrip($request);
        return ResponseHelper::success($result);
    }

    public function searchCollageTrips(Request $request)
    {
        $trips = $this->tripService->searchByDestination($request->destination);
        return ResponseHelper::success($trips);
    }

    public function dailyReservations()
    {
        $result = $this->tripService->dailyReservations();
        return ResponseHelper::success($result);
    }

    public function checkCost(CheckCostRequest $request): JsonResponse
    {
        $result = $this->tripService->checkCost($request);
        return ResponseHelper::success($result);
    }

    public function payDailyReservation(PayRequest $request) //TODO
    {
        $user = User::findOrFail(auth('sanctum')->id());
        $reservation = DailyCollageReservation::findOrFail($request->reservation_id);
        $this->tripService->payReservation($user, $reservation);
        return ResponseHelper::success('Paid successfully');
    }

    public function userReservations(Request $request)
    {
        $user = User::findOrFail(auth('sanctum')->id());
        $reservations = $this->tripService->usersCollageReservations($user, $request->date, $request->status);
        return ResponseHelper::success($reservations);
    }

    public function driverTrips(Request $request)
    {
        $result = $this->tripService->getDriverTrips($request);
        return ResponseHelper::success($result);
    }

    // public function dailyReservationInfo(Request $request)
    // {

    //     $result = [];
    //     return ResponseHelper::success($result);
    // }
}
