<?php

namespace App\Http\Controllers;

use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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

        $startYear = date('Y', strtotime($startDate));
        $endYear = date('Y', strtotime($endDate));
        $startMonth = date('m', strtotime($startDate));
        $endMonth = date('m', strtotime($endDate));

        $cacheKey = 'statistics_' . $startDate . '_' . $endDate . '_' . $destinationId . '_' . $groupBy;

        // Retrieve cached results if available
        $allStatistics = Cache::remember($cacheKey, 2, function () use ($startDate, $endDate, $destinationId, $groupBy, $startYear, $endYear, $startMonth, $endMonth) {
            $statistics = [];

            if ($groupBy === 'year') {
                for ($year = $startYear; $year <= $endYear; $year++) {
                    $currentStartDate = ($year == $startYear) ? $startDate : $year . '-01-01';
                    $currentEndDate = ($year == $endYear) ? $endDate : $year . '-12-31';

                    $orderCount = $this->getOrderCount($destinationId, $currentStartDate, $currentEndDate);
                    $statistics[] = (object)[
                        'period' => $year,
                        'order_count' => $orderCount,
                    ];
                }
            } elseif ($groupBy === 'month') {
                for ($year = $startYear; $year <= $endYear; $year++) {
                    $start = ($year == $startYear) ? $startMonth : 1;
                    $end = ($year == $endYear) ? $endMonth : 12;

                    for ($month = $start; $month <= $end; $month++) {
                        $currentStartDate = ($year == $startYear && $month == $startMonth) ? $startDate : sprintf('%04d-%02d-01', $year, $month);
                        $currentEndDate = ($year == $endYear && $month == $endMonth) ? $endDate : date('Y-m-t', strtotime($currentStartDate));

                        $orderCount = $this->getOrderCount($destinationId, $currentStartDate, $currentEndDate);
                        $statistics[] = (object)[
                            'period' => sprintf('%04d-%02d', $year, $month),
                            'order_count' => $orderCount,
                        ];
                    }
                }
            }

            return $statistics;
        });

        $response = [
            'statistics' => $allStatistics
        ];
        return ResponseHelper::success($response);
    }

    private function getOrderCount($destinationId, $startDate, $endDate)
    {
        return Order::join('reservation_orders', 'orders.id', '=', 'reservation_orders.order_id')
            ->join('reservations', 'reservation_orders.reservation_id', '=', 'reservations.id')
            ->join('trips', 'trips.id', '=', 'reservations.trip_id')
            ->where('trips.destination_id', $destinationId)
            ->where('trips.status', 'done')
            ->whereBetween('trips.date', [$startDate, $endDate])
            ->distinct('orders.id')
            ->count('orders.id');
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
