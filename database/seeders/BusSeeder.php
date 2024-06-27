<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class BusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Get some existing ids from image_of_buses table
       // $imageOfBusIds = DB::table('image_of_buses')->pluck('id')->toArray();

        for ($i = 0; $i < 20; $i++) {
            DB::table('buses')->insert([
                'bus_number' => $faker->unique()->numberBetween(1000, 9999),
                'type' => $faker->randomElement(['Standard', 'Double Decker', 'Mini Bus']),
                'image' => $faker->imageUrl(640, 480, 'transport', true, 'bus'),
                'number_of_seats' => $faker->numberBetween(20, 50),
//                'image_of_buse_id' => $faker->randomElement($imageOfBusIds),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
