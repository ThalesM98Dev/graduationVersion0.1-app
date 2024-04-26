<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    use HasFactory;


    protected $fillable = ['bus_number', 'type', 'number_of_seats', 'seats'];

    protected $casts = [
        'seats' => 'array',
    ];

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
