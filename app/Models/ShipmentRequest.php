<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentRequest extends Model
{
    use HasFactory;

    public function shipmentTrip()
    {
        return $this->belongsTo(ShipmentTrip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function foodstuffs()
    {
        return $this->hasMany(Foodstuff::class);
    }

    public function shipmentFoodstuffs()
    {
        return $this->hasMany(ShipmentFoodstuff::class);
    }
}
