<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;
 

     protected $guarded = ['id'];
    protected $fillable = [
        'order_id',
        'trip_id',
        'seat_number',
    ];
    protected $casts = [
        'seat_number' => 'array',
    ];

}
