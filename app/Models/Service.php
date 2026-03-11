<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer',
        'is_active' => 'boolean',
    ];

    public function transactionItems()
    {
        return $this->morphMany(TransactionItem::class, 'item');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    public function bookingItems()
    {
        return $this->hasMany(BookingItem::class);
    }
}