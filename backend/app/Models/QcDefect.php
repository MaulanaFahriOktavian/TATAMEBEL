<?php

namespace App\Models;

use App\Enums\QcDefectSeverity;
use App\Enums\QcDefectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcDefect extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workshop_id',
        'qc_inspection_id',
        'qc_item_id',
        'description',
        'severity',
        'status',
        'resolution',
        'resolved_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'severity' => QcDefectSeverity::class,
            'status' => QcDefectStatus::class,
            'resolved_at' => 'datetime',
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

    public function qcItem(): BelongsTo
    {
        return $this->belongsTo(QcItem::class);
    }
}
