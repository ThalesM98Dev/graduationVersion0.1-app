<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile_number',
        'password',
        'fcm_token',
        'age',
        'address',
        'nationality',
        'role',
        'isVerified'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'fcm_token'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function shipmentRequests()
    {
        return $this->hasMany(ShipmentRequest::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function dailyCollageReservations()
    {
        return $this->hasMany(DailyCollageReservation::class);
    }

    public function collageTrip()
    {
        return $this->hasMany(CollageTrip::class, 'driver_id');
    }

    public function envelops(): HasMany
    {
        return $this->hasMany(Envelope::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(FcmNotification::class);
    }
}
