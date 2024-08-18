<?php

use App\Models\Trip;
use App\Models\Reservation;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;

class EndTripTest extends TestCase
{
    // public function test_endTrip_successful(): void
    // {
    //     $this->assertTrue(true);
    // }

    // public function test_endTrip_notAllReservationsConfirmed(): void
    // {
    //     $user = User::factory()->make();
    //     // Manually create a Trip record with the default status 'pending'
    //     $trip = Trip::create();

    //     // Don't confirm any reservations for the trip

    //     $response = $this->actingAs($user, 'api')->put("/endTrip/{$trip->id}");

    //     $response->assertStatus(404);

    //     // Assert that the trip status remains 'pending'
    //     $this->assertEquals('pending', Trip::find($trip->id)->status);
    // }

    // public function test_endTrip_invalidTripId(): void
    // {
    //     $user = User::factory()->make();
    //     $nonExistentTripId = 999; // Assuming this trip ID does not exist

    //     $response = $this->actingAs($user, 'api')->put("/endTrip/{$nonExistentTripId}");

    //     $response->assertStatus(404);
    // }
}