<?php

namespace App\Services;

use App\Enums\MediaVisibility;
use App\Models\Media;
use App\Models\Order;
use App\Models\ProductionUpdate;
use App\Models\QcDefect;
use App\Models\QcInspection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaService
{
    /**
     * Allowed MIME types for photo evidence.
     *
     * @var list<string>
     */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Maximum file size in bytes (10 MB).
     */
    public const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Upload and register a media evidence file.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function upload(
        Order $order,
        UploadedFile $file,
        array $data,
        ?User $actor = null,
        ?ProductionUpdate $update = null,
        ?QcInspection $qcInspection = null,
        ?QcDefect $qcDefect = null
    ): Media {
        // Validate MIME type
        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'file' => ['Format berkas tidak didukung. Format yang diizinkan: JPG, PNG, WEBP.'],
            ]);
        }

        // Validate File Size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw ValidationException::withMessages([
                'file' => ['Ukuran berkas melebihi batas maksimal 10 MB.'],
            ]);
        }

        $visibility = isset($data['visibility'])
            ? MediaVisibility::from($data['visibility'])
            : MediaVisibility::INTERNAL;

        // Segregate physical storage disk by visibility:
        // CUSTOMER media goes to 'public' disk (served for public portal).
        // INTERNAL media goes to 'local' private disk (storage/app/private, inaccessible via public URL).
        $disk = $visibility === MediaVisibility::CUSTOMER ? 'public' : 'local';
        $folder = "workshops/{$order->workshop_id}/orders/{$order->id}/media";
        $storedPath = $file->store($folder, $disk);

        $media = Media::create([
            'workshop_id' => $order->workshop_id,
            'order_id' => $order->id,
            'production_update_id' => $update?->id ?? ($data['production_update_id'] ?? null),
            'qc_inspection_id' => $qcInspection?->id ?? ($data['qc_inspection_id'] ?? null),
            'qc_defect_id' => $qcDefect?->id ?? ($data['qc_defect_id'] ?? null),
            'uploaded_by' => $actor->id,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'visibility' => $visibility,
            'caption' => $data['caption'] ?? null,
        ]);

        ActivityLogService::log(
            workshopId: $order->workshop_id,
            action: 'MEDIA_UPLOADED',
            entity: $media,
            description: "Foto bukti pengerjaan diunggah ({$visibility->value}) untuk pesanan {$order->order_number}.",
            user: $actor,
            orderId: $order->id,
            metadata: [
                'media_id' => $media->id,
                'file_name' => $media->original_name,
                'visibility' => $visibility->value,
                'production_update_id' => $update?->id,
            ]
        );

        return $media;
    }

    /**
     * Delete a media record and remove the underlying file from storage.
     */
    public function delete(Media $media, ?User $actor = null): bool
    {
        $disk = $media->visibility === MediaVisibility::CUSTOMER ? 'public' : 'local';

        if (Storage::disk($disk)->exists($media->file_path)) {
            Storage::disk($disk)->delete($media->file_path);
        }

        $media->delete();

        return true;
    }
}
