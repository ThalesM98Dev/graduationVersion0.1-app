<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Foodstuff extends Model
{
    use HasFactory;

     public function shipmentRequests()
    {
        return $this->hasMany(ShipmentRequest::class, 'id', 'foodstuff_id');
    }
}
