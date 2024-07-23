<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $primaryKey = 'id';
    protected $casts = [
        'seats' => 'array',
    ];

    public function destination()
    {

        return $this->belongsTo(Destination::class);
    }

    //public function orders()
    //{
    //    return $this->hasManyThrough(Order::class, Reservation::class, 'trip_id', 'reservation_id');
    //}
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function bus()
    {

        return $this->belongsTo(Bus::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class);
    }

    public function stations()
    {
        return $this->belongsToMany(Destination::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function collageTrip(): BelongsTo
    {
        return $this->belongsTo(CollageTrip::class);
    }

    public function dailyCollageReservation(): HasMany
    {
        return $this->hasMany(DailyCollageReservation::class);
    }
<<<<<<< HEAD

    public function envelops(): HasMany
    {
        return $this->hasMany(Envelope::class);
    }
=======
>>>>>>> origin/main
}
