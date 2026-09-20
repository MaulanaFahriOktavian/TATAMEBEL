<?php

namespace Tests\Feature\Database;

use App\Enums\ChangeRequestStatus;
use App\Enums\MediaVisibility;
use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Enums\ProductionStageStatus;
use App\Enums\QcDefectSeverity;
use App\Enums\QcDefectStatus;
use App\Enums\QcInspectionStatus;
use App\Enums\QcItemStatus;
use App\Enums\ShippingStatus;
use App\Enums\SpecificationStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductionStage;
use App\Models\ProductionUpdate;
use App\Models\QcDefect;
use App\Models\QcInspection;
use App\Models\QcItem;
use App\Models\Shipping;
use App\Models\Specification;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_order_relationship_graph(): void
    {
        // 1. Workshop & Users
        $workshop = Workshop::factory()->create();
        $owner = User::factory()->create([
            'workshop_id' => $workshop->id,
            'role' => UserRole::OWNER,
        ]);
        $productionUser = User::factory()->create([
            'workshop_id' => $workshop->id,
            'role' => UserRole::PRODUCTION,
        ]);
        $qcUser = User::factory()->create([
            'workshop_id' => $workshop->id,
            'role' => UserRole::QC,
        ]);

        $this->assertTrue($workshop->users->contains($owner));
        $this->assertEquals($workshop->id, $owner->workshop->id);

        // 2. Customer
        $customer = Customer::factory()->create([
            'workshop_id' => $workshop->id,
        ]);
        $this->assertTrue($workshop->customers->contains($customer));
        $this->assertEquals($workshop->id, $customer->workshop->id);

        // 3. Order
        $order = Order::factory()->create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'status' => OrderStatus::IN_PRODUCTION,
        ]);
        $this->assertTrue($workshop->orders->contains($order));
        $this->assertTrue($customer->orders->contains($order));
        $this->assertEquals($customer->id, $order->customer->id);

        // 4. OrderItem & Specification
        $item = OrderItem::factory()->create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
        ]);
        $this->assertTrue($order->orderItems->contains($item));
        $this->assertEquals($order->id, $item->order->id);

        $spec = Specification::create([
            'workshop_id' => $workshop->id,
            'order_item_id' => $item->id,
            'version' => 1,
            'status' => SpecificationStatus::LOCKED,
            'locked_at' => now(),
            'locked_by' => $owner->id,
            'material' => 'Solid Teak',
        ]);
        $this->assertTrue($item->specifications->contains($spec));
        $this->assertEquals($item->id, $spec->orderItem->id);
        $this->assertEquals($owner->id, $spec->lockedBy->id);

        // 5. ChangeRequest
        $cr = ChangeRequest::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'requested_by' => 'Pelanggan Pak Hendra',
            'description' => 'Tinggikan kaki meja 5 cm',
            'status' => ChangeRequestStatus::APPROVED,
            'approved_by' => $owner->id,
            'approved_at' => now(),
        ]);
        $this->assertTrue($order->changeRequests->contains($cr));
        $this->assertEquals($owner->id, $cr->approver->id);

        // 6. ProductionStage & ProductionUpdate
        $stage = ProductionStage::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'name' => 'Cutting',
            'sequence' => 1,
            'status' => ProductionStageStatus::COMPLETED,
        ]);
        $this->assertTrue($order->productionStages->contains($stage));

        $update = ProductionUpdate::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'production_stage_id' => $stage->id,
            'user_id' => $productionUser->id,
            'description' => 'Pemotongan papan kayu jati selesai',
            'progress_snapshot' => 25.00,
        ]);
        $this->assertTrue($stage->productionUpdates->contains($update));
        $this->assertEquals($productionUser->id, $update->user->id);

        // 7. QC Inspection, Items, Defects
        $inspection = QcInspection::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'inspected_by' => $qcUser->id,
            'status' => QcInspectionStatus::FAILED,
            'notes' => 'Terdapat goresan halus di permukaan meja',
            'inspected_at' => now(),
        ]);
        $this->assertTrue($order->qcInspections->contains($inspection));
        $this->assertEquals($qcUser->id, $inspection->inspector->id);

        $qcItem = QcItem::create([
            'workshop_id' => $workshop->id,
            'qc_inspection_id' => $inspection->id,
            'category' => 'surface',
            'item' => 'Kehalusan amplas top table',
            'status' => QcItemStatus::FAIL,
        ]);
        $this->assertTrue($inspection->qcItems->contains($qcItem));

        $defect = QcDefect::create([
            'workshop_id' => $workshop->id,
            'qc_inspection_id' => $inspection->id,
            'qc_item_id' => $qcItem->id,
            'description' => 'Scratch mikro 3cm di sudut kanan',
            'severity' => QcDefectSeverity::MEDIUM,
            'status' => QcDefectStatus::OPEN,
        ]);
        $this->assertTrue($inspection->qcDefects->contains($defect));
        $this->assertEquals($qcItem->id, $defect->qcItem->id);

        // 8. Media (photo evidence for production update & qc inspection)
        $mediaProduction = Media::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'production_update_id' => $update->id,
            'uploaded_by' => $productionUser->id,
            'file_path' => 'photos/evidence_1.jpg',
            'original_name' => 'evidence_1.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 204800,
            'visibility' => MediaVisibility::CUSTOMER,
        ]);
        $this->assertTrue($update->media->contains($mediaProduction));
        $this->assertEquals($order->id, $mediaProduction->order->id);
        $this->assertEquals($productionUser->id, $mediaProduction->uploader->id);

        $mediaQc = Media::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'qc_inspection_id' => $inspection->id,
            'uploaded_by' => $qcUser->id,
            'file_path' => 'photos/defect_1.jpg',
            'original_name' => 'defect_1.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 150000,
            'visibility' => MediaVisibility::INTERNAL,
        ]);
        $this->assertTrue($inspection->media->contains($mediaQc));

        // 9. Payment
        $payment = Payment::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'type' => PaymentType::DP,
            'amount' => 5000000.00,
            'payment_date' => now()->toDateString(),
            'method' => 'Bank Transfer BCA',
            'confirmed_by' => $owner->id,
            'confirmed_at' => now(),
        ]);
        $this->assertTrue($order->payments->contains($payment));
        $this->assertEquals($owner->id, $payment->confirmedBy->id);

        // 10. Shipping (HasOne)
        $shipping = Shipping::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'courier' => 'Workshop Fleet',
            'shipping_address' => 'Jl. Tebet Barat No. 10, Jakarta Selatan',
            'status' => ShippingStatus::PENDING,
        ]);
        $this->assertEquals($shipping->id, $order->shipping->id);
        $this->assertEquals($order->id, $shipping->order->id);

        // 11. ActivityLog
        $log = ActivityLog::create([
            'workshop_id' => $workshop->id,
            'user_id' => $owner->id,
            'order_id' => $order->id,
            'action' => 'ORDER_CREATED',
            'entity_type' => Order::class,
            'entity_id' => $order->id,
            'description' => 'Pesanan baru dibuat untuk customer Pak Hendra',
            'metadata' => ['initial_status' => 'DRAFT'],
        ]);
        $this->assertTrue($order->activityLogs->contains($log));
        $this->assertEquals($owner->id, $log->user->id);
    }
}
