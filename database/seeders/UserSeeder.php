<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 60; $i++) {
            DB::table('users')->insert([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'mobile_number' => $faker->phoneNumber(),
                'isVerified' => true,
                'verification_code' => null,
                'password' => Hash::make('password'), // Or use bcrypt() function
                'age' => $faker->numberBetween(18, 70),
                'address' => $faker->address(),
                'nationality' => $faker->country(),
                'points' => $faker->numberBetween(0, 10000),
                'role' => $faker->randomElement(['User', 'Driver', 'Shipment Employee', 'Travel Trips Employee', 'University trips Employee', 'Admin']),
                'remember_token' => $faker->lexify('????????????????????????????????????????'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
