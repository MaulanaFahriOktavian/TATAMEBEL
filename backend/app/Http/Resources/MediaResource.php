<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\Media
 */
class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'production_update_id' => $this->production_update_id,
            'file_path' => $this->file_path,
            'url' => Storage::disk('public')->url($this->file_path),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'file_size' => (int) $this->file_size,
            'visibility' => $this->visibility->value,
            'caption' => $this->caption,
            'uploader' => $this->uploader ? [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
