<?php

namespace App\Console\Commands;

use App\Models\CollageTrip;
use App\Models\Trip;
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
    public function handle()
    {
        try {
            Log::info('Starting TripDateCommand');

            $collageTrips = CollageTrip::with('days')->get();
            Log::info('Fetched collage trips', ['collageTrips' => $collageTrips]);

            $latestTrip = Trip::latest()->first();
            $latestTripNumber = $latestTrip ? $latestTrip->trip_number : 0;
            Log::info('Latest trip number', ['latestTripNumber' => $latestTripNumber]);

            DB::transaction(function () use ($collageTrips, &$latestTripNumber) {
                foreach ($collageTrips as $collageTrip) {
                    foreach ($collageTrip->days as $day) {
                        // Calculate next week's date for the specific day
                        $nextWeekTripDate = Carbon::now()->next($day->name);
                        Log::info('Calculated next week trip date', ['nextWeekTripDate' => $nextWeekTripDate->format('Y-m-d')]);
                        // Create a new trip
                        Trip::create([
                            'trip_number' => ++$latestTripNumber,
                            'collage_trip_id' => $collageTrip->id,
                            'date' => $nextWeekTripDate->format('Y-m-d'),
                            'trip_type' => 'Universities',
                            'available_seats' => 30
                        ]);
                        Log::info('Created trip', ['trip_number' => $latestTripNumber, 'collage_trip_id' => $collageTrip->id, 'date' => $nextWeekTripDate->format('Y-m-d')]);
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
