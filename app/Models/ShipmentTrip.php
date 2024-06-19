<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentTrip extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    public function destination()
    {

        return $this->belongsTo(Destination::class);
    }

    public function truck()
    {

        return $this->belongsTo(Truck::class);
    }

    public function shipmentRequests()
    {
        return $this->hasMany(ShipmentRequest::class);
    }


}
