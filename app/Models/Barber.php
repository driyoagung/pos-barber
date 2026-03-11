<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barber extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialty',
        'hire_date',
        'commission_rate',
        'is_active'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}