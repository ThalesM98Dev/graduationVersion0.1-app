<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CollageTrip extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function days(): BelongsToMany
    {
        return $this->belongsToMany(Day::class, 'trips_days');
    }
    public function driver(): HasOne
    {
        return $this->hasOne(User::class, 'driver_id');
    }
}
