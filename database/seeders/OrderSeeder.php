<?php

namespace Database\Seeders;

use App\Enum\RolesEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Get some existing ids from users table
        $userIds = DB::table('users')->where('role', RolesEnum::USER->value)->pluck('id')->toArray();

        for ($i = 0; $i < 20; $i++) {
            DB::table('orders')->insert([
                'name' => $faker->name(),
                'mobile_number' => $faker->phoneNumber(),
                'age' => $faker->numberBetween(18, 70),
                'address' => $faker->address(),
                'nationality' => $faker->country(),
                'image_of_ID' => $faker->optional()->imageUrl(640, 480, 'people', true, 'ID'),
                'image_of_passport' => $faker->optional()->imageUrl(640, 480, 'people', true, 'passport'),
                'image_of_security_clearance' => $faker->optional()->imageUrl(640, 480, 'people', true, 'security clearance'),
                'image_of_visa' => $faker->optional()->imageUrl(640, 480, 'people', true, 'visa'),
                'user_id' => $faker->randomElement($userIds),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
