<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Day extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function collageTrips(): BelongsToMany
    {
        return $this->belongsToMany(CollageTrip::class, 'trips_days');
    }

    public function dailyCollageReservation()
    {
        return $this->hasMany(DailyCollageReservation::class);
    }
}
