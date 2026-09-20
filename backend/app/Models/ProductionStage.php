<?php

namespace App\Models;

use App\Enums\ProductionStageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionStage extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workshop_id',
        'order_id',
        'name',
        'sequence',
        'status',
        'started_at',
        'completed_at',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'status' => ProductionStageStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_active' => 'boolean',
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

    public function productionUpdates(): HasMany
    {
        return $this->hasMany(ProductionUpdate::class);
    }
}
