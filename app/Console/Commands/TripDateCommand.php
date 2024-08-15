<?php

namespace App\Console\Commands;

use App\Models\CollageTrip;
use App\Models\Trip;
use App\Services\TripService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TripDateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:trip-date-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to schedule trips for the next week';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting TripDateCommand');
            //
            $collageTrips = CollageTrip::with('days')->get();
            Log::info('Fetched collage trips', ['collageTrips' => $collageTrips]);
            //
            $latestTrip = Trip::latest()->first();
            $latestTripNumber = $latestTrip ? $latestTrip->trip_number : 0;
            Log::info('Latest trip number', ['latestTripNumber' => $latestTripNumber]);
            //
            DB::transaction(function () use ($collageTrips, &$latestTripNumber) {
                foreach ($collageTrips as $collageTrip) {
                    $subscriptions = $collageTrip->subscriptions()
                        ->where('status', 'accepted')
                        ->whereDate('start_date', '>=', $collageTrip->start_date)
                        ->whereDate('end_date', '<=', $collageTrip->end_date)
                        ->get();
                    Log::info('Fetched collage trip subscriptions', ['collageTrip_id ' => $collageTrip->id, 'subscriptions' => $subscriptions]);
                    //
                    foreach ($collageTrip->days as $day) {
                        // Calculate next week's date for the specific day
                        $nextWeekTripDate = Carbon::now()->next($day->name);
                        $trip = app(TripService::class)->checkTripExistence($nextWeekTripDate, $collageTrip->id);
                        Log::info('collage trip existence', ['result' => $trip]);
                        //check if the trip exists
                        if (!$trip) {
                            Log::info('Calculated next week trip date', ['nextWeekTripDate' => $nextWeekTripDate->format('Y-m-d')]);
                            $latestCollageTrip = Trip::where('trip_type', 'Universities')->latest()->first();//??
                            Log::info('Latest collage trip', ['latestCollageTrip' => $latestCollageTrip]);
                            // Create a new trip
                            $trip = Trip::create([
                                'trip_number' => ++$latestTripNumber,
                                'collage_trip_id' => $collageTrip->id,
                                'date' => $nextWeekTripDate->format('Y-m-d'),
                                'trip_type' => 'Universities',
                                'available_seats' => $latestCollageTrip->total_seats,
                                'total_seats' => $latestCollageTrip->total_seats,
                                'driver_id' => $collageTrip->driver_id
                            ]);
                        }
                        foreach ($subscriptions as $subscription) {
                            $reservation = app(TripService::class)
                                ->checkReservationExistence($subscription, $trip->id);
                            if (!$reservation) {
                                $reservation = $subscription->reservation()->create([
                                    'trip_id' => $trip->id,
                                    'status' => 'accept',
                                    'count_of_persons' => 1,
                                ]);
                                Log::info('Reservation', ['Reservation' => $reservation]);
                                $trip->available_seats--;
                                $trip->save();
                            }
                        }
                        Log::info('Created trip', ['trip_number' => $latestTripNumber,
                            'collage_trip_id' => $collageTrip->id, 'date' => $nextWeekTripDate->format('Y-m-d')]);
                    }
                }
            });
            $this->info('Trips scheduled successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to schedule trips: ' . $e->getMessage());
            $this->error('Failed to schedule trips. Check the logs for more details.');
        }
    }
}
