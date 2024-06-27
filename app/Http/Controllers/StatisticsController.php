<?php

namespace App\Http\Controllers;

use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    private $tripService;

    public function __construct(TripService $tripService)
    {
        $this->tripService = $tripService;
    }


    public function byDateAndDestenation(Request $request)
{
    $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'destination_id' => 'required|exists:destinations,id',
        'group_by' => 'required|in:year,month',
    ]);

    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $destinationId = $request->input('destination_id');
    $groupBy = $request->input('group_by'); // 'year' or 'month'

    $query = Order::join('reservation_orders', 'orders.id', '=', 'reservation_orders.order_id')
        ->join('reservations', 'reservation_orders.reservation_id', '=', 'reservations.id')
        ->join('trips', 'trips.id', '=', 'reservations.trip_id')
        ->whereBetween('trips.date', [$startDate, $endDate])
        ->where('trips.destination_id', $destinationId);

    if ($groupBy === 'year') {
        $query->groupBy(DB::raw('YEAR(trips.date)'))
            ->select(DB::raw('YEAR(trips.date) as period, COUNT(DISTINCT orders.id) as order_count'))
            ->orderBy('period', 'asc');
    } elseif ($groupBy === 'month') {
        $query->groupBy(DB::raw('MONTH(trips.date)'))
            ->select(DB::raw('MONTH(trips.date) as period, COUNT(DISTINCT orders.id) as order_count'))
            ->orderBy('period', 'asc');
    }

    $statistics = $query->get();

    $response = [
        'statistics' => $statistics
    ];
    return ResponseHelper::success($response);
}


    /*
       * Statistics
     */

    public function tripsCountPerDatePeriod(Request $request): JsonResponse
    {
        $statistics = $this->tripService->tripsCountPerDatePeriod($request);
        return ResponseHelper::success($statistics);
    }

    public function tripsCountDestinationPeriod(Request $request): JsonResponse
    {
        $statistics = $this->tripService->tripsCountDestinationPeriod($request);
        return ResponseHelper::success($statistics);
    }


}
