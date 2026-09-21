<?php

namespace App\Services;

use App\Enums\ChangeRequestStatus;
use App\Enums\SpecificationStatus;
use App\Models\ChangeRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Specification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeRequestService
{
    /**
     * Allowed fields that can be altered via requested_changes.
     *
     * @var list<string>
     */
    public const ALLOWED_CHANGE_FIELDS = [
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
     * Submit a formal Change Request for an order item whose specification is LOCKED.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(Order $order, array $data, ?User $actor = null): ChangeRequest
    {
        // 1. Resolve and verify order item
        $orderItem = $order->orderItems()->whereKey($data['order_item_id'])->first();

        if (! $orderItem) {
            throw ValidationException::withMessages([
                'order_item_id' => ['Item pesanan tidak ditemukan pada pesanan ini.'],
            ]);
        }

        // 2. Resolve current operational specification (highest version that is LOCKED)
        $currentSpec = $orderItem->currentSpecification();

        if (! $currentSpec) {
            throw ValidationException::withMessages([
                'order_item_id' => ['Change Request hanya dapat dibuat untuk item yang sudah memiliki spesifikasi berstatus LOCKED.'],
            ]);
        }

        // 3. Verify there is no pending change request already open for this item
        $hasPending = $orderItem->changeRequests()
            ->where('status', ChangeRequestStatus::PENDING)
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'order_item_id' => ['Item pesanan ini masih memiliki Change Request yang berstatus PENDING.'],
            ]);
        }

        // 4. Validate requested_changes JSON structure
        $requestedChanges = $data['requested_changes'] ?? [];

        if (! is_array($requestedChanges) || empty($requestedChanges)) {
            throw ValidationException::withMessages([
                'requested_changes' => ['requested_changes harus berupa objek/array data terstruktur dan tidak boleh kosong.'],
            ]);
        }

        // Filter only allowed technical specification fields
        $filteredChanges = array_intersect_key($requestedChanges, array_flip(self::ALLOWED_CHANGE_FIELDS));

        if (empty($filteredChanges)) {
            throw ValidationException::withMessages([
                'requested_changes' => ['requested_changes tidak memuat field spesifikasi teknis yang valid untuk diubah.'],
            ]);
        }

        $changeRequest = ChangeRequest::create([
            'workshop_id' => $order->workshop_id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'specification_id' => $currentSpec->id,
            'current_version' => $currentSpec->version,
            'requested_by' => $data['requested_by'],
            'user_id' => $actor?->id,
            'description' => $data['description'],
            'reason' => $data['reason'] ?? null,
            'requested_changes' => $filteredChanges,
            'status' => ChangeRequestStatus::PENDING,
        ]);

        ActivityLogService::log(
            workshopId: $order->workshop_id,
            action: 'CHANGE_REQUEST_CREATED',
            entity: $changeRequest,
            description: "Change Request #{$changeRequest->id} diajukan untuk item {$orderItem->product_name} (spesifikasi v{$currentSpec->version}).",
            user: $actor,
            orderId: $order->id,
            metadata: [
                'order_item_id' => $orderItem->id,
                'specification_id' => $currentSpec->id,
                'current_version' => $currentSpec->version,
                'requested_by' => $changeRequest->requested_by,
                'requested_fields' => array_keys($filteredChanges),
            ]
        );

        return $changeRequest->load(['orderItem', 'specification', 'user']);
    }

    /**
     * Approve a Change Request, transactionally spawning a new specification version in DRAFT status.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function approve(ChangeRequest $changeRequest, array $data = [], ?User $actor = null): Specification
    {
        if ($changeRequest->status !== ChangeRequestStatus::PENDING) {
            throw ValidationException::withMessages([
                'change_request' => ["Change Request tidak dapat disetujui karena berstatus {$changeRequest->status->value}."],
            ]);
        }

        return DB::transaction(function () use ($changeRequest, $data, $actor) {
            // Lock change request row
            $cr = ChangeRequest::where('id', $changeRequest->id)->lockForUpdate()->first();

            // 1. Mark Change Request APPROVED
            $cr->update([
                'status' => ChangeRequestStatus::APPROVED,
                'approved_by' => $actor?->id,
                'approved_at' => now(),
                'review_note' => $data['review_note'] ?? null,
            ]);

            // 2. Fetch original specification baseline
            $originalSpec = $cr->specification ?? $cr->orderItem->specifications()
                ->where('version', $cr->current_version)
                ->firstOrFail();

            // 3. Compute new version number
            $maxVersion = (int) ($cr->orderItem->specifications()->max('version') ?? $cr->current_version);
            $newVersion = $maxVersion + 1;

            // 4. Construct new specification attributes from original + requested changes
            $newAttributes = [
                'workshop_id' => $cr->workshop_id,
                'order_item_id' => $cr->order_item_id,
                'version' => $newVersion,
                'width' => $originalSpec->width,
                'height' => $originalSpec->height,
                'depth' => $originalSpec->depth,
                'dimension_unit' => $originalSpec->dimension_unit,
                'material' => $originalSpec->material,
                'wood_grade' => $originalSpec->wood_grade,
                'finishing' => $originalSpec->finishing,
                'color' => $originalSpec->color,
                'fabric' => $originalSpec->fabric,
                'design_reference' => $originalSpec->design_reference,
                'special_request' => $originalSpec->special_request,
                'production_note' => $originalSpec->production_note,
                'status' => SpecificationStatus::DRAFT, // Per Clarification 3: Newly created spec is DRAFT
                'locked_at' => null,
                'locked_by' => null,
            ];

            // Apply deterministic structured changes
            $changes = $cr->requested_changes ?? [];
            foreach ($changes as $key => $val) {
                if (in_array($key, self::ALLOWED_CHANGE_FIELDS, true)) {
                    $newAttributes[$key] = $val;
                }
            }

            $newSpec = Specification::create($newAttributes);

            // Log activity logs
            ActivityLogService::log(
                workshopId: $cr->workshop_id,
                action: 'CHANGE_REQUEST_APPROVED',
                entity: $cr,
                description: "Change Request #{$cr->id} disetujui oleh {$actor?->name}.",
                user: $actor,
                orderId: $cr->order_id,
                metadata: [
                    'order_item_id' => $cr->order_item_id,
                    'new_specification_id' => $newSpec->id,
                    'new_version' => $newSpec->version,
                    'review_note' => $cr->review_note,
                ]
            );

            ActivityLogService::log(
                workshopId: $cr->workshop_id,
                action: 'SPECIFICATION_VERSION_CREATED',
                entity: $newSpec,
                description: "Spesifikasi versi baru (v{$newSpec->version} DRAFT) dibuat dari persetujuan Change Request #{$cr->id}.",
                user: $actor,
                orderId: $cr->order_id,
                metadata: [
                    'change_request_id' => $cr->id,
                    'previous_version' => $cr->current_version,
                    'version' => $newSpec->version,
                    'applied_changes' => $changes,
                ]
            );

            return $newSpec->load(['orderItem']);
        });
    }

    /**
     * Reject a Change Request with an audit note.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function reject(ChangeRequest $changeRequest, array $data, ?User $actor = null): ChangeRequest
    {
        if ($changeRequest->status !== ChangeRequestStatus::PENDING) {
            throw ValidationException::withMessages([
                'change_request' => ["Change Request tidak dapat ditolak karena berstatus {$changeRequest->status->value}."],
            ]);
        }

        $reviewNote = $data['review_note'] ?? null;
        if (empty($reviewNote)) {
            throw ValidationException::withMessages([
                'review_note' => ['Catatan peninjauan (alasan penolakan) wajib diisi saat menolak Change Request.'],
            ]);
        }

        $changeRequest->update([
            'status' => ChangeRequestStatus::REJECTED,
            'approved_by' => $actor?->id,
            'approved_at' => now(),
            'review_note' => $reviewNote,
        ]);

        ActivityLogService::log(
            workshopId: $changeRequest->workshop_id,
            action: 'CHANGE_REQUEST_REJECTED',
            entity: $changeRequest,
            description: "Change Request #{$changeRequest->id} ditolak oleh {$actor?->name}.",
            user: $actor,
            orderId: $changeRequest->order_id,
            metadata: [
                'order_item_id' => $changeRequest->order_item_id,
                'review_note' => $reviewNote,
            ]
        );

        return $changeRequest->load(['orderItem', 'specification', 'approver']);
    }

    /**
     * Cancel a pending Change Request.
     *
     * @throws ValidationException
     */
    public function cancel(ChangeRequest $changeRequest, ?User $actor = null): ChangeRequest
    {
        if ($changeRequest->status !== ChangeRequestStatus::PENDING) {
            throw ValidationException::withMessages([
                'change_request' => ["Change Request tidak dapat dibatalkan karena berstatus {$changeRequest->status->value}."],
            ]);
        }

        $changeRequest->update([
            'status' => ChangeRequestStatus::CANCELLED,
        ]);

        ActivityLogService::log(
            workshopId: $changeRequest->workshop_id,
            action: 'CHANGE_REQUEST_CANCELLED',
            entity: $changeRequest,
            description: "Change Request #{$changeRequest->id} dibatalkan.",
            user: $actor,
            orderId: $changeRequest->order_id,
            metadata: [
                'order_item_id' => $changeRequest->order_item_id,
            ]
        );

        return $changeRequest;
    }
}
