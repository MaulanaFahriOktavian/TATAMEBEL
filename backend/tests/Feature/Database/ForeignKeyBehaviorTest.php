<?php

namespace Tests\Feature\Database;

use App\Enums\OrderStatus;
use App\Enums\QcDefectSeverity;
use App\Enums\QcDefectStatus;
use App\Enums\QcInspectionStatus;
use App\Enums\QcItemStatus;
use App\Enums\ShippingStatus;
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
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForeignKeyBehaviorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that deleting a customer with existing orders is restricted by the database.
     */
    public function test_customer_deletion_is_restricted_when_orders_exist(): void
    {
        $workshop = Workshop::factory()->create();
        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);
        Order::factory()->create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
        ]);

        $this->expectException(QueryException::class);

        // Attempting to delete customer who owns orders must fail via restrictOnDelete
        $customer->delete();
    }

    /**
     * Test that deleting a workshop cascades and deletes tenant entities.
     */
    public function test_workshop_deletion_cascades_to_tenant_entities(): void
    {
        $workshop = Workshop::factory()->create();
        $user = User::factory()->create(['workshop_id' => $workshop->id]);
        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);

        $workshop->delete();

        $this->assertDatabaseMissing('workshops', ['id' => $workshop->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    /**
     * Test that deleting a workshop with orders respects the customer restrict rule.
     */
    public function test_workshop_deletion_respects_customer_restrict_rule(): void
    {
        $workshop = Workshop::factory()->create();
        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);
        $order = Order::factory()->create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
        ]);

        try {
            $workshop->delete();
            $this->fail('Expected QueryException because orders.customer_id is ON DELETE RESTRICT');
        } catch (QueryException $e) {
            $this->assertStringContainsString('1451', $e->getMessage());
        }

        // Once orders are removed, workshop deletion cascades cleanly
        $order->delete();
        $workshop->delete();

        $this->assertDatabaseMissing('workshops', ['id' => $workshop->id]);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    /**
     * Test that deleting an order cascades to its items, stages, and shipping.
     */
    public function test_order_deletion_cascades_to_order_children(): void
    {
        $workshop = Workshop::factory()->create();
        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);
        $order = Order::factory()->create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
        ]);
        $item = OrderItem::factory()->create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
        ]);
        $stage = ProductionStage::factory()->create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
        ]);
        $shipping = Shipping::factory()->create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
        ]);

        $order->delete();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('production_stages', ['id' => $stage->id]);
        $this->assertDatabaseMissing('shipping', ['id' => $shipping->id]);
    }

    /**
     * Test that deleting a qc_inspection sets media.qc_inspection_id to null (nullOnDelete).
     */
    public function test_qc_inspection_deletion_nulls_media_foreign_key(): void
    {
        $workshop = Workshop::factory()->create();
        $user = User::factory()->create(['workshop_id' => $workshop->id]);
        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);
        $order = Order::factory()->create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
        ]);
        $qc = QcInspection::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'inspected_by' => $user->id,
            'status' => QcInspectionStatus::PENDING,
        ]);
        $media = Media::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'qc_inspection_id' => $qc->id,
            'uploaded_by' => $user->id,
            'file_path' => 'evidence.jpg',
            'original_name' => 'evidence.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1000,
        ]);

        $qc->delete();

        $this->assertDatabaseMissing('qc_inspections', ['id' => $qc->id]);
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'qc_inspection_id' => null,
        ]);
    }

    /**
     * Test that deleting a qc_item sets qc_defects.qc_item_id to null (nullOnDelete).
     */
    public function test_qc_item_deletion_nulls_defect_foreign_key(): void
    {
        $workshop = Workshop::factory()->create();
        $user = User::factory()->create(['workshop_id' => $workshop->id]);
        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);
        $order = Order::factory()->create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
        ]);
        $qc = QcInspection::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'inspected_by' => $user->id,
            'status' => QcInspectionStatus::FAILED,
        ]);
        $qcItem = QcItem::create([
            'workshop_id' => $workshop->id,
            'qc_inspection_id' => $qc->id,
            'category' => 'construction',
            'item' => 'Joint Mortise & Tenon',
            'status' => QcItemStatus::FAIL,
        ]);
        $defect = QcDefect::create([
            'workshop_id' => $workshop->id,
            'qc_inspection_id' => $qc->id,
            'qc_item_id' => $qcItem->id,
            'description' => 'Gap 2mm on joint',
            'severity' => QcDefectSeverity::HIGH,
            'status' => QcDefectStatus::OPEN,
        ]);

        $qcItem->delete();

        $this->assertDatabaseMissing('qc_items', ['id' => $qcItem->id]);
        $this->assertDatabaseHas('qc_defects', [
            'id' => $defect->id,
            'qc_item_id' => null,
        ]);
    }
}
