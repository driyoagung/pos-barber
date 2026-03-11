<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'barber_id',
        'booking_date',
        'booking_time',
        'status',
        'notes'
    ];

    protected $casts = [
        'booking_date' => 'date',
        'booking_time' => 'datetime:H:i',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function barber()
    {
        return $this->belongsTo(Barber::class);
    }

    public function items()
    {
        return $this->hasMany(BookingItem::class);
    }
}