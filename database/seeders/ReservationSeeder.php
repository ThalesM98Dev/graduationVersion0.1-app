<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Get some existing ids from trips table
        $tripIds = DB::table('trips')->pluck('id')->toArray();

        for ($i = 0; $i < 80; $i++) {
            DB::table('reservations')->insert([
                'trip_id' => $faker->randomElement($tripIds),
                'status' => $faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
                'total_price' => $faker->numberBetween(100, 10000),
                'count_of_persons' => $faker->numberBetween(1, 10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
