<?php

namespace App\Services;

use App\Enums\SpecificationStatus;
use App\Models\OrderItem;
use App\Models\Specification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SpecificationService
{
    /**
     * Allowed technical specification fields for creation and updates.
     *
     * @var list<string>
     */
    public const SPEC_FIELDS = [
        'width',
        'height',
        'depth',
        'dimension_unit',
        'material',
        'wood_grade',
        'finishing',
        'color',
        'fabric',
        'design_reference',
        'special_request',
        'production_note',
    ];

    /**
     * Create an initial DRAFT specification for an order item.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(OrderItem $orderItem, array $data, ?User $actor = null): Specification
    {
        // Check if there is already a DRAFT specification for this item
        $existingDraft = $orderItem->specifications()
            ->where('status', SpecificationStatus::DRAFT)
            ->first();

        if ($existingDraft) {
            throw ValidationException::withMessages([
                'order_item_id' => ['Item pesanan ini sudah memiliki spesifikasi dalam status DRAFT (versi '.$existingDraft->version.'). Selesaikan atau kunci spesifikasi tersebut terlebih dahulu.'],
            ]);
        }

        // Determine version number
        $latestVersion = (int) ($orderItem->specifications()->max('version') ?? 0);
        $nextVersion = $latestVersion + 1;

        $attributes = array_intersect_key($data, array_flip(self::SPEC_FIELDS));

        $specification = Specification::create([
            'workshop_id' => $orderItem->workshop_id,
            'order_item_id' => $orderItem->id,
            'version' => $nextVersion,
            'status' => SpecificationStatus::DRAFT,
            ...$attributes,
        ]);

        ActivityLogService::log(
            workshopId: $orderItem->workshop_id,
            action: 'SPECIFICATION_CREATED',
            entity: $specification,
            description: "Spesifikasi versi {$specification->version} (DRAFT) dibuat untuk item {$orderItem->product_name}.",
            user: $actor,
            orderId: $orderItem->order_id,
            metadata: [
                'order_item_id' => $orderItem->id,
                'version' => $specification->version,
                'status' => $specification->status->value,
            ]
        );

        return $specification->load(['orderItem']);
    }

    /**
     * Update a specification in DRAFT status.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(Specification $specification, array $data, ?User $actor = null): Specification
    {
        if ($specification->status === SpecificationStatus::LOCKED) {
            throw ValidationException::withMessages([
                'specification' => ['Spesifikasi yang sudah dikunci (LOCKED) tidak dapat diubah secara langsung. Gunakan Change Request.'],
            ]);
        }

        $attributes = array_intersect_key($data, array_flip(self::SPEC_FIELDS));
        $specification->update($attributes);

        ActivityLogService::log(
            workshopId: $specification->workshop_id,
            action: 'SPECIFICATION_UPDATED',
            entity: $specification,
            description: "Spesifikasi versi {$specification->version} untuk item {$specification->orderItem->product_name} diperbarui.",
            user: $actor,
            orderId: $specification->orderItem->order_id,
            metadata: [
                'order_item_id' => $specification->order_item_id,
                'version' => $specification->version,
                'updated_fields' => array_keys($attributes),
            ]
        );

        return $specification->load(['orderItem']);
    }

    /**
     * Lock a specification so it becomes the authoritative manufacturing blueprint.
     *
     * @throws ValidationException
     */
    public function lock(Specification $specification, ?User $actor = null): Specification
    {
        if ($specification->status === SpecificationStatus::LOCKED) {
            return $specification;
        }

        $specification->update([
            'status' => SpecificationStatus::LOCKED,
            'locked_at' => now(),
            'locked_by' => $actor?->id,
        ]);

        ActivityLogService::log(
            workshopId: $specification->workshop_id,
            action: 'SPECIFICATION_LOCKED',
            entity: $specification,
            description: "Spesifikasi versi {$specification->version} untuk item {$specification->orderItem->product_name} berhasil dikunci (LOCKED).",
            user: $actor,
            orderId: $specification->orderItem->order_id,
            metadata: [
                'order_item_id' => $specification->order_item_id,
                'version' => $specification->version,
                'locked_at' => $specification->locked_at->toIso8601String(),
                'locked_by' => $actor?->id,
            ]
        );

        return $specification->load(['orderItem', 'lockedBy']);
    }

    /**
     * List all specification versions for an order item in descending order.
     *
     * @return Collection<int, Specification>
     */
    public function listVersions(OrderItem $orderItem): Collection
    {
        return $orderItem->specifications()
            ->with(['lockedBy'])
            ->orderByDesc('version')
            ->get();
    }

    /**
     * Get the current operational specification (highest version that is LOCKED).
     */
    public function getCurrent(OrderItem $orderItem): ?Specification
    {
        return $orderItem->currentSpecification();
    }
}
