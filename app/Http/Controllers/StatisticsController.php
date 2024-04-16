<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Trip;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Helpers\ImageUploadHelper;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
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

       $query = Reservation::join('trips', 'trips.id', '=', 'reservations.trip_id')
        ->whereBetween('trips.date', [$startDate, $endDate])
        ->where('trips.destination_id', $destinationId);

    if ($groupBy === 'year') {
        $query->groupBy(DB::raw('YEAR(trips.date)'))
            ->select(DB::raw('YEAR(trips.date) as period, COUNT(*) as reservation_count'))
            ->orderBy('period', 'asc');
    } elseif ($groupBy === 'month') {
        $query->groupBy(DB::raw('MONTH(trips.date)'))
            ->select(DB::raw('MONTH(trips.date) as period, COUNT(*) as reservation_count'))
            ->orderBy('period', 'asc');
    }

    $statistics = $query->get();

    $response = [
        'statistics' => $statistics
    ];
    return ResponseHelper::success($response);
  }
}
