<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'trip_id',
        'total_price',
        'count_of_persons',
        'status',
        'subscription_id'
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function reservationOrders()
    {
        return $this->hasMany(ReservationOrder::class, 'reservation_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'reservation_orders', 'reservation_id', 'order_id')->withTimestamps();
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
