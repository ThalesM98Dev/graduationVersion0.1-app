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
            $collageTrips = CollageTrip::all();
            $latestTrip = Trip::latest()->first();
            $latestTripNumber = $latestTrip ? $latestTrip->trip_number : 0;

            DB::transaction(function() use ($collageTrips, &$latestTripNumber) {
                foreach ($collageTrips as $collageTrip) {
                    $currentTripDate = Carbon::parse($collageTrip->day);
                    $nextWeekTripDate = $currentTripDate->copy()->addWeek();

                    Trip::create([
                        'trip_number' => ++$latestTripNumber,
                        'collage_trip_id' => $collageTrip->id,
                        'date' => $nextWeekTripDate->format('Y-m-d'),
                        'trip_type' => 'Universities',
                    ]);
                }
            });

            $this->info('Trips scheduled successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to schedule trips: ' . $e->getMessage());
            $this->error('Failed to schedule trips. Check the logs for more details.');
        }
    }
}
