<?php

namespace Tests\Feature\Qc;

use App\Enums\OrderStatus;
use App\Enums\ProductionStageStatus;
use App\Enums\QcDefectStatus;
use App\Enums\QcInspectionStatus;
use App\Enums\QcItemStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductionStage;
use App\Models\QcDefect;
use App\Models\QcInspection;
use App\Models\QcItem;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QcPackingGateTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;
    private User $admin;
    private User $qc;
    private Order $order;
    private OrderItem $orderItem;
    private ProductionStage $qcStage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workshop = Workshop::factory()->create();

        $this->admin = User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $this->qc = User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'role' => UserRole::QC,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create(['workshop_id' => $this->workshop->id]);

        $this->order = Order::factory()->create([
            'workshop_id' => $this->workshop->id,
            'customer_id' => $customer->id,
            'status' => OrderStatus::QC,
        ]);

        $this->orderItem = OrderItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'product_name' => 'Lemari Pakaian 3 Pintu',
        ]);

        $this->qcStage = ProductionStage::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'name' => 'QC',
            'sequence' => 7,
            'status' => ProductionStageStatus::IN_PROGRESS,
            'is_active' => true,
        ]);
    }

    public function test_order_cannot_move_from_qc_to_packing_without_any_qc_inspection(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/orders/{$this->order->id}/status", [
                'status' => 'PACKING',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertEquals(OrderStatus::QC, $this->order->fresh()->status);
    }

    public function test_order_cannot_move_to_packing_if_latest_inspection_is_rework_or_failed(): void
    {
        // 1. REWORK inspection
        $inspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::REWORK,
            'inspected_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/orders/{$this->order->id}/status", [
                'status' => 'PACKING',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // 2. FAILED inspection
        $inspection->update(['status' => QcInspectionStatus::FAILED]);

        $response2 = $this->actingAs($this->admin)
            ->patchJson("/api/v1/orders/{$this->order->id}/status", [
                'status' => 'PACKING',
            ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_order_cannot_move_to_packing_if_open_or_in_rework_defects_exist(): void
    {
        $inspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::PASSED,
            'inspected_at' => now(),
        ]);

        QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcItemStatus::PASS,
        ]);

        QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcDefectStatus::OPEN,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/orders/{$this->order->id}/status", [
                'status' => 'PACKING',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_order_cannot_move_to_packing_if_checklist_items_are_unevaluated_or_fail(): void
    {
        $inspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::PASSED,
            'inspected_at' => now(),
        ]);

        QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcItemStatus::FAIL,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/orders/{$this->order->id}/status", [
                'status' => 'PACKING',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_order_moves_to_packing_successfully_when_passed_inspection_and_all_defects_resolved_or_accepted(): void
    {
        $inspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::PASSED,
            'inspected_at' => now(),
        ]);

        QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcItemStatus::PASS,
        ]);

        QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcDefectStatus::RESOLVED,
            'resolution' => 'Sudah diamplas dan dicat ulang',
        ]);

        QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcDefectStatus::ACCEPTED,
            'resolution' => 'Corak alami diterima owner',
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/orders/{$this->order->id}/status", [
                'status' => 'PACKING',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'PACKING');

        $this->assertEquals(OrderStatus::PACKING, $this->order->fresh()->status);
        $this->assertEquals(ProductionStageStatus::COMPLETED, $this->qcStage->fresh()->status);
    }

    public function test_order_state_cannot_reverse_from_qc_to_in_production(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson("/api/v1/orders/{$this->order->id}/status", [
                'status' => 'IN_PRODUCTION',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
