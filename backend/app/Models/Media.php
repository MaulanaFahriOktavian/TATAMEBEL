<?php

namespace App\Models;

use App\Enums\MediaVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'media';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workshop_id',
        'order_id',
        'production_update_id',
        'qc_inspection_id',
        'uploaded_by',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'visibility',
        'caption',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'visibility' => MediaVisibility::class,
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

    public function productionUpdate(): BelongsTo
    {
        return $this->belongsTo(ProductionUpdate::class);
    }

    public function qcInspection(): BelongsTo
    {
        return $this->belongsTo(QcInspection::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
