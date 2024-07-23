<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'reservation_id',
        'seat_number',
    ];
    protected $casts = [
        'seat_number' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
