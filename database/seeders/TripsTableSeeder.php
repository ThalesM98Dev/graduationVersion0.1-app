<?php

namespace Database\Seeders;

use App\Enum\RolesEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class TripsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Get some existing ids from related tables
        $destinationIds = DB::table('destinations')->pluck('id')->toArray();
        $busIds = DB::table('buses')->pluck('id')->toArray();
        $driverIds = DB::table('users')->where('role', RolesEnum::DRIVER->value)->pluck('id')->toArray();
        //$collageTripIds = DB::table('collage_trips')->pluck('id')->toArray();

        for ($i = 0; $i < 50; $i++) {
            DB::table('trips')->insert([
                'trip_number' => $faker->numberBetween(1000, 9999),
                'date' => $faker->date(),
                'available_seats' => $faker->numberBetween(0, 50),
                'total_seats' => $faker->numberBetween(50, 100),
                'depature_hour' => $faker->time(),
                'arrival_hour' => $faker->time(),
                'trip_type' => 'External',
                'starting_place' => $faker->city(),
                'price' => $faker->numberBetween(100, 1000),
                'status' => $faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
                'destination_id' => $faker->randomElement($destinationIds),
                'bus_id' => $faker->randomElement($busIds),
                'driver_id' => $faker->randomElement($driverIds),
               // 'collage_trip_id' => $faker->randomElement($collageTripIds),
                'seats' => json_encode($faker->words($faker->numberBetween(1, 5))),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
