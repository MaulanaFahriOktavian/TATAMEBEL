<?php

namespace App\Models;

use App\Enums\QcInspectionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QcInspection extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workshop_id',
        'order_id',
        'inspected_by',
        'status',
        'notes',
        'inspected_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status' => QcInspectionStatus::class,
            'inspected_at' => 'datetime',
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

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function qcItems(): HasMany
    {
        return $this->hasMany(QcItem::class);
    }

    public function qcDefects(): HasMany
    {
        return $this->hasMany(QcDefect::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }
}
