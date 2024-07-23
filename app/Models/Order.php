<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'address','mobile_number','nationality', 'age', 'image_of_ID','image_of_passport','image_of_security_clearance','image_of_visa','user_id'];

    public function user(){

        return $this->belongsTo(User::class);
    }

   // public function trips(){

     //   return $this->belongsToMany(Trip::class ,'reservations' , 'order_id' , 'trip_id')->withTimestamps();
   // }

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class, 'reservation_orders', 'order_id', 'reservation_id')->using(ReservationOrder::class)->withTimestamps();
    }

}
