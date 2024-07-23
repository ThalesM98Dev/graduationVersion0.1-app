<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ReservationOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Get some existing ids from orders and reservations tables
        $orderIds = DB::table('orders')->pluck('id')->toArray();
        $reservationIds = DB::table('reservations')->pluck('id')->toArray();

        for ($i = 0; $i < 20; $i++) {
            DB::table('reservation_orders')->insert([
                'order_id' => $faker->randomElement($orderIds),
                'reservation_id' => $faker->randomElement($reservationIds),
                'seat_number' => $faker->numberBetween(1, 50),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
