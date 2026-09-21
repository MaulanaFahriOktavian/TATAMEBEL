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
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionStageQcSyncTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;
    private User $admin;
    private User $production;
    private User $qc;
    private Order $order;
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

        $this->production = User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'role' => UserRole::PRODUCTION,
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

        // Initialize 8 default stages
        app(ProductionService::class)->initializeDefaultStages($this->order, $this->admin);

        // Mark stages 1 to 6 completed
        $this->order->productionStages()
            ->where('sequence', '<=', 6)
            ->update([
                'status' => ProductionStageStatus::COMPLETED,
                'completed_at' => now(),
            ]);

        $this->qcStage = $this->order->productionStages()
            ->where('sequence', 7)
            ->first();

        $this->qcStage->update(['status' => ProductionStageStatus::IN_PROGRESS, 'started_at' => now()]);
    }

    public function test_passing_qc_inspection_automatically_completes_qc_production_stage(): void
    {
        $inspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::PENDING,
        ]);

        QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcItemStatus::PASS,
        ]);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$inspection->id}/finalize", [
                'status' => 'PASSED',
                'notes' => 'Inspeksi QC lulus sempurna',
            ]);

        $response->assertStatus(200);

        $this->qcStage->refresh();
        $this->assertEquals(ProductionStageStatus::COMPLETED, $this->qcStage->status);
        $this->assertNotNull($this->qcStage->completed_at);

        // Verify progress is 7/8 = 87.5%
        $progress = app(ProductionService::class)->calculateProgress($this->order);
        $this->assertEquals(87.5, $progress);
    }

    public function test_rework_inspection_does_not_complete_qc_stage(): void
    {
        $inspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::PENDING,
        ]);

        QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcItemStatus::FAIL,
        ]);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$inspection->id}/finalize", [
                'status' => 'REWORK',
                'notes' => 'Perlu perbaikan finishing',
            ]);

        $response->assertStatus(200);

        $this->qcStage->refresh();
        $this->assertEquals(ProductionStageStatus::IN_PROGRESS, $this->qcStage->status);
        $this->assertNull($this->qcStage->completed_at);
    }

    public function test_production_staff_cannot_complete_qc_stage_manually(): void
    {
        $response = $this->actingAs($this->production)
            ->patchJson("/api/v1/production-stages/{$this->qcStage->id}", [
                'status' => 'COMPLETED',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertEquals(ProductionStageStatus::IN_PROGRESS, $this->qcStage->fresh()->status);
    }

    public function test_creating_inspection_transitions_pending_qc_stage_to_in_progress(): void
    {
        $this->qcStage->update(['status' => ProductionStageStatus::PENDING, 'started_at' => null]);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/orders/{$this->order->id}/qc-inspections", [
                'notes' => 'Mulai sesi inspeksi QC',
            ]);

        $response->assertStatus(201);

        $this->qcStage->refresh();
        $this->assertEquals(ProductionStageStatus::IN_PROGRESS, $this->qcStage->status);
        $this->assertNotNull($this->qcStage->started_at);
    }
}
