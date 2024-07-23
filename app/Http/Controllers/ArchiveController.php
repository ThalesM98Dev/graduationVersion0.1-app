<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Helpers\ResponseHelper;
use App\Models\Destination;
use App\Models\User;
use App\Models\Trip;
use App\Models\Archive;
use App\Models\Reservation;
use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class ArchiveController extends Controller
{
    public function showArchive()
    {
        $trips = Trip::where('status', 'done')
            ->with('destination', 'bus', 'driver')
            ->get();

        $response = [
            'trips' => $trips
        ];
        return ResponseHelper::success($response);
    }
}
