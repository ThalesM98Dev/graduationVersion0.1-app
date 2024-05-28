<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $primaryKey = 'id';

    public function destination()
    {

        return $this->belongsTo(Destination::class);
    }

    public function orders()
{
    return $this->hasManyThrough(Order::class, Reservation::class, 'trip_id', 'reservation_id');
}
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
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
