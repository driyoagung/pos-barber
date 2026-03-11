<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'points'
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}