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
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductionStage;
use App\Models\QcDefect;
use App\Models\QcInspection;
use App\Models\QcItem;
use App\Models\Shipping;
use App\Models\Specification;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnumCastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_12_enums_cast_correctly_on_models(): void
    {
        $workshop = Workshop::factory()->create();

        // 1. UserRole
        $user = User::factory()->create([
            'workshop_id' => $workshop->id,
            'role' => UserRole::QC,
        ]);
        $user->refresh();
        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertEquals(UserRole::QC, $user->role);

        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);

        // 2. OrderStatus
        $order = Order::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-ENUM-01',
            'title' => 'Casting Test Order',
            'status' => OrderStatus::READY_FOR_PRODUCTION,
            'public_token' => Str::random(64),
        ]);
        $order->refresh();
        $this->assertInstanceOf(OrderStatus::class, $order->status);
        $this->assertEquals(OrderStatus::READY_FOR_PRODUCTION, $order->status);

        $item = OrderItem::factory()->create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
        ]);

        // 3. SpecificationStatus
        $spec = Specification::create([
            'workshop_id' => $workshop->id,
            'order_item_id' => $item->id,
            'version' => 1,
            'status' => SpecificationStatus::LOCKED,
        ]);
        $spec->refresh();
        $this->assertInstanceOf(SpecificationStatus::class, $spec->status);
        $this->assertEquals(SpecificationStatus::LOCKED, $spec->status);

        // 4. ChangeRequestStatus
        $cr = ChangeRequest::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'requested_by' => 'Customer',
            'description' => 'Test',
            'status' => ChangeRequestStatus::PENDING,
        ]);
        $cr->refresh();
        $this->assertInstanceOf(ChangeRequestStatus::class, $cr->status);
        $this->assertEquals(ChangeRequestStatus::PENDING, $cr->status);

        // 5. ProductionStageStatus
        $stage = ProductionStage::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'name' => 'Assembly',
            'sequence' => 1,
            'status' => ProductionStageStatus::IN_PROGRESS,
        ]);
        $stage->refresh();
        $this->assertInstanceOf(ProductionStageStatus::class, $stage->status);
        $this->assertEquals(ProductionStageStatus::IN_PROGRESS, $stage->status);

        // 6. MediaVisibility
        $media = Media::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'uploaded_by' => $user->id,
            'file_path' => 'test.jpg',
            'original_name' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'visibility' => MediaVisibility::CUSTOMER,
        ]);
        $media->refresh();
        $this->assertInstanceOf(MediaVisibility::class, $media->visibility);
        $this->assertEquals(MediaVisibility::CUSTOMER, $media->visibility);

        // 7. QcInspectionStatus
        $qc = QcInspection::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'inspected_by' => $user->id,
            'status' => QcInspectionStatus::REWORK,
        ]);
        $qc->refresh();
        $this->assertInstanceOf(QcInspectionStatus::class, $qc->status);
        $this->assertEquals(QcInspectionStatus::REWORK, $qc->status);

        // 8. QcItemStatus
        $qcItem = QcItem::create([
            'workshop_id' => $workshop->id,
            'qc_inspection_id' => $qc->id,
            'category' => 'finishing',
            'item' => 'Gloss level',
            'status' => QcItemStatus::NA,
        ]);
        $qcItem->refresh();
        $this->assertInstanceOf(QcItemStatus::class, $qcItem->status);
        $this->assertEquals(QcItemStatus::NA, $qcItem->status);

        // 9 & 10. QcDefectSeverity and QcDefectStatus
        $defect = QcDefect::create([
            'workshop_id' => $workshop->id,
            'qc_inspection_id' => $qc->id,
            'description' => 'Minor paint bubble',
            'severity' => QcDefectSeverity::CRITICAL,
            'status' => QcDefectStatus::IN_REWORK,
        ]);
        $defect->refresh();
        $this->assertInstanceOf(QcDefectSeverity::class, $defect->severity);
        $this->assertEquals(QcDefectSeverity::CRITICAL, $defect->severity);
        $this->assertInstanceOf(QcDefectStatus::class, $defect->status);
        $this->assertEquals(QcDefectStatus::IN_REWORK, $defect->status);

        // 11. PaymentType
        $payment = Payment::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'type' => PaymentType::FINAL,
            'amount' => 15000000.00,
            'payment_date' => now()->toDateString(),
            'method' => 'Transfer',
        ]);
        $payment->refresh();
        $this->assertInstanceOf(PaymentType::class, $payment->type);
        $this->assertEquals(PaymentType::FINAL, $payment->type);

        // 12. ShippingStatus
        $shipping = Shipping::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'courier' => 'Deliveree',
            'shipping_address' => 'Jl. Kemang Raya No. 1',
            'status' => ShippingStatus::SHIPPED,
        ]);
        $shipping->refresh();
        $this->assertInstanceOf(ShippingStatus::class, $shipping->status);
        $this->assertEquals(ShippingStatus::SHIPPED, $shipping->status);
    }
}
