<?php

namespace App\Models;

use App\Enums\ShippingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipping extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'shipping';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workshop_id',
        'order_id',
        'courier',
        'tracking_number',
        'shipping_address',
        'shipped_at',
        'estimated_arrival',
        'delivered_at',
        'status',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShippingStatus::class,
            'shipped_at' => 'datetime',
            'estimated_arrival' => 'date',
            'delivered_at' => 'datetime',
        ];
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
