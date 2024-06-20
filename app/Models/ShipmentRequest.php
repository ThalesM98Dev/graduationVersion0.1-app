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

    public function shipment()
    {
        return $this->hasMany(ShipmentRequest::class);
    }
    public function shipmentFoodstuffs()
    {
        return $this->hasMany(ShipmentFoodstuff::class, 'reservation_id');
    }
}
