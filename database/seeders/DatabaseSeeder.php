<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(DaysSeeder::class);
        $this->call(BusSeeder::class);
        $this->call(DestinationSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(TripsTableSeeder::class);
        $this->call(OrderSeeder::class);
        $this->call(ReservationSeeder::class);
        $this->call(ReservationOrderSeeder::class);
//        User::factory()->create([
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//        ]);
    }
}
