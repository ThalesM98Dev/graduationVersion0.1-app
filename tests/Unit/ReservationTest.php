<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Destination;
use App\Models\Bus;
use Illuminate\Foundation\Testing\WithFaker;

class ReservationTest extends TestCase
{
    use WithFaker;

    public function test_createReservation_with_valid_data(): void
  {
    $user = User::factory()->make(); // Create a fake user instance

    $data = [
        'orders' => [
            [
                'name' => 'John Doe',
                'address' => '123 Street',
                'mobile_number' => '1234567890',
                'age' => 30,
                'nationality' => 'US',
            ]
        ],
        'seat_numbers' => [1, 2],
        'trip_id' => 1,
    ];
    //dd($user);

    $userId = $user->id; // Updating the parameter key
    $response = $this->actingAs($user, 'api')->post('api/reserv/creatReservation/{userId}', $data);

    $response->assertStatus(200);
   }

    public function test_addTrip_with_valid_data(): void
  {
    $user = User::factory()->make();
    $destination_id = Destination::get()->random()->id;
    $bus_id = Bus::get()->random()->id;
    $driver_id = User::get()->random()->id;
    $data = [
        'trip_number' => 234567,
        'price' => 1000,
        'date' =>'2024-08-12',
        'depature_hour' => '10:30',
        'trip_type'=> "Trip",
        'starting_place'=> "damas",
        'destination_id'=> $destination_id,
        'bus_id'=> $bus_id,
        'driver_id'=>$driver_id,
    ];
    //dd($data);

    $response = $this->actingAs($user, 'api')->post('api/trip/add_trip', $data);

    $response->assertStatus(200);
   }
}