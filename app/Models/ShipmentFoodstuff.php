<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentFoodstuff extends Model
{
    use HasFactory;

    public function shipmentRequest()
    {
        return $this->belongsTo(ShipmentRequest::class);
    }

    public function foodstuff()
    {
        return $this->belongsTo(Foodstuff::class);
    }
}
