<?php

namespace App\Models;

use App\Enums\QcItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QcItem extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workshop_id',
        'qc_inspection_id',
        'category',
        'item',
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
            'status' => QcItemStatus::class,
        ];
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function qcInspection(): BelongsTo
    {
        return $this->belongsTo(QcInspection::class);
    }

    public function qcDefects(): HasMany
    {
        return $this->hasMany(QcDefect::class);
    }
}
