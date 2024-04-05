<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    public function destination(){

        return $this->belongsTo(Destination::class);
    }

    public function orders(){

        return $this->belongsToMany(Order::class ,'reservations' , 'trip_id' , 'order_id')->withTimestamps();
    }

    public function bus(){

        return $this->belongsTo(Bus::class);
    }
}
