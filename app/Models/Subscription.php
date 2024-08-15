<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function collageTrip()
    {
        return $this->belongsTo(CollageTrip::class);
    }
    public function reservation()
    {
        return $this->hasMany(Reservation::class);
    }
}
