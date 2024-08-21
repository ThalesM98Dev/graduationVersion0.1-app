<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    //protected $fillable = ['name'];

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
    
    public function shipmentTrips()
    {
        return $this->hasMany(ShipmentTrip::class);
    }

    public function collageTrips()
    {
        return $this->belongsToMany(Trip::class);
    }
}
