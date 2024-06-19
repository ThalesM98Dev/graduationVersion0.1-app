<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\CreateCollageTripRequest;
use App\Http\Requests\UpdateCollageTripRequest;
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

    public function show($trip_id)
    {
        $result = $this->tripService->collageTripDetails($trip_id);
        return ResponseHelper::success($result);
    }

    public function create(CreateCollageTripRequest $request): JsonResponse
    {
        $result = $this->tripService->createCollageTrip($request);
        return ResponseHelper::success($result, 'Created successfully.');
    }


    public function update(UpdateCollageTripRequest $request): JsonResponse
    {
        $result = $this->tripService->updateCollageTrip($request);
        return ResponseHelper::success($result, 'Updated successfully');
    }

    public function destroy($trip_id)//TODO
    {
        $result = $this->tripService->deleteCollageTrip($trip_id);
        return ResponseHelper::success($result, 'Deleted successfully');
    }

    public function bookDailyCollageTrip(Request $request)
    {
        $result = $this->tripService->bookDailyCollageTrip($request);
        return ResponseHelper::success($result, 'Booked successfully');
    }

    public function searchCollageTrips(Request $request)
    {
        $trips = $this->tripService->searchByDestination($request->destination);
        return ResponseHelper::success($trips);
    }


}
