<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    public function shipmentTrips()
    {
        return $this->hasMany(ShipmentTrip::class);
    }
}
