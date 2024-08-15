<?php

namespace Database\Seeders;

use App\Enum\RolesEnum;
use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Couchbase\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(DaysSeeder::class);

        User::create([
            'name' => 'First Admin',
            'email' => 'first.admin@mail.com',
            'mobile_number' => '0321654987',
            'password' => Hash::make('password'),
            'isVerified' => true,
            'role' => RolesEnum::ADMIN->value,
            'age' => 33,
            'address' => 'Syria',
        ]);
        User::create([
            'name' => 'First Driver',
            'email' => 'first.driver@mail.com',
            'mobile_number' => '0321654981',
            'password' => Hash::make('password'),
            'isVerified' => true,
            'role' => RolesEnum::DRIVER->value,
            'age' => 33,
            'address' => 'Syria',
        ]);
        User::create([
            'name' => 'First User',
            'email' => 'first.User@mail.com',
            'mobile_number' => '0321654982',
            'password' => Hash::make('password'),
            'isVerified' => true,
            'role' => RolesEnum::USER->value,
            'age' => 33,
            'address' => 'Syria',
        ]);
        // $this->call(BusSeeder::class);
        // $this->call(DestinationSeeder::class);
        //         $this->call(UserSeeder::class);
        // $this->call(TripsTableSeeder::class);
        // $this->call(OrderSeeder::class);
        // $this->call(ReservationSeeder::class);
        // $this->call(ReservationOrderSeeder::class);
        //        User::factory()->create([
        //            'name' => 'Test User',
        //            'email' => 'test@example.com',
        //        ]);
    }
}
