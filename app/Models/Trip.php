<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_number',
        'date',
        'depature_hour',
        'back_hour',
        'trip_type',
        'starting_place',
        'destination_id',
        'bus_id',
        'driver_id',
        'price',
        'semester_price',
        'daily_points',
        'semester_points',
        'available_seats'
    ];

    public function destination()
    {

        return $this->belongsTo(Destination::class);
    }

    public function orders()
    {

        return $this->belongsToMany(Order::class, 'reservations', 'trip_id', 'order_id')->withTimestamps();
    }

    public function bus()
    {

        return $this->belongsTo(Bus::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class);
    }

    public function stations()
    {
        return $this->belongsToMany(Destination::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
