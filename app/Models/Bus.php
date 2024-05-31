<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    use HasFactory;
    protected $fillable = ['bus_number', 'type', 'number_of_seats'];

    public function trip()
    {
        return $this->hasOne(Trip::class);
    }

    public function ImageOfBus()
    {

        return $this->belongsTo(ImageOfBus::class);
    }
}
