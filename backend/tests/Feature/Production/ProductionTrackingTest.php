<?php

namespace Tests\Feature\Production;

use App\Enums\OrderStatus;
use App\Enums\ProductionStageStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductionStage;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshopA;
    private Workshop $workshopB;
    private User $ownerA;
    private User $adminA;
    private User $productionA;
    private User $qcA;
    private User $ownerB;
    private Order $orderA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workshopA = Workshop::factory()->create();
        $this->workshopB = Workshop::factory()->create();

        $this->ownerA = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'role' => UserRole::OWNER,
            'is_active' => true,
        ]);

        $this->adminA = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $this->productionA = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'role' => UserRole::PRODUCTION,
            'is_active' => true,
        ]);

        $this->qcA = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'role' => UserRole::QC,
            'is_active' => true,
        ]);

        $this->ownerB = User::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'role' => UserRole::OWNER,
            'is_active' => true,
        ]);

        $customerA = Customer::factory()->create(['workshop_id' => $this->workshopA->id]);
        $this->orderA = Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $customerA->id,
            'status' => OrderStatus::READY_FOR_PRODUCTION,
        ]);
    }

    public function test_owner_and_admin_can_initialize_8_default_stages(): void
    {
        $token = $this->adminA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$this->orderA->id}/production/init-stages");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(8, 'data')
            ->assertJsonPath('data.0.name', 'Material Preparation')
            ->assertJsonPath('data.0.sequence', 1)
            ->assertJsonPath('data.1.name', 'Cutting')
            ->assertJsonPath('data.2.name', 'Assembly')
            ->assertJsonPath('data.3.name', 'Sanding')
            ->assertJsonPath('data.4.name', 'Finishing')
            ->assertJsonPath('data.5.name', 'Final Assembly')
            ->assertJsonPath('data.6.name', 'QC')
            ->assertJsonPath('data.7.name', 'Packing')
            ->assertJsonPath('data.7.sequence', 8);

        $this->assertDatabaseCount('production_stages', 8);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'action' => 'PRODUCTION_STAGE_CREATED',
        ]);
    }

    public function test_authoritative_progress_calculation_formula(): void
    {
        $adminToken = $this->adminA->createToken('test-token')->plainTextToken;

        // Initialize 8 stages
        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/v1/orders/{$this->orderA->id}/production/init-stages");

        // 0 completed -> progress = 0.00%
        $overview0 = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson("/api/v1/orders/{$this->orderA->id}/production");

        $overview0->assertStatus(200)
            ->assertJsonPath('data.progress_percentage', 0)
            ->assertJsonPath('data.total_active_stages', 8)
            ->assertJsonPath('data.completed_active_stages', 0);

        // Complete 4 stages (Material Preparation, Cutting, Assembly, Sanding)
        $stages = ProductionStage::where('order_id', $this->orderA->id)->orderBy('sequence')->get();
        for ($i = 0; $i < 4; $i++) {
            $stages[$i]->update(['status' => ProductionStageStatus::COMPLETED]);
        }

        // 4 of 8 completed -> progress = 50.00%
        $overview4 = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson("/api/v1/orders/{$this->orderA->id}/production");

        $overview4->assertStatus(200)
            ->assertJsonPath('data.progress_percentage', 50)
            ->assertJsonPath('data.completed_active_stages', 4);

        // Deactivate 1 stage (e.g. stage 8: Packing is deactivated for this order)
        // Now total active = 7, completed active = 4 -> 4/7 * 100 = 57.14%
        $stages[7]->update(['is_active' => false]);

        $overviewDeactivated = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson("/api/v1/orders/{$this->orderA->id}/production");

        $overviewDeactivated->assertStatus(200)
            ->assertJsonPath('data.progress_percentage', 57.14)
            ->assertJsonPath('data.total_active_stages', 7)
            ->assertJsonPath('data.completed_active_stages', 4);
    }

    public function test_production_staff_can_update_stage_status_and_stamps_timestamps(): void
    {
        $stage = ProductionStage::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'name' => 'Cutting',
            'sequence' => 2,
            'status' => ProductionStageStatus::PENDING,
        ]);

        $prodToken = $this->productionA->createToken('prod-token')->plainTextToken;

        // Transition to IN_PROGRESS
        $resProgress = $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->patchJson("/api/v1/production-stages/{$stage->id}", [
                'status' => 'IN_PROGRESS',
            ]);

        $resProgress->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'IN_PROGRESS');

        $stage->refresh();
        $this->assertEquals(ProductionStageStatus::IN_PROGRESS, $stage->status);
        $this->assertNotNull($stage->started_at);
        $this->assertNull($stage->completed_at);

        // Transition to COMPLETED
        $resCompleted = $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->patchJson("/api/v1/production-stages/{$stage->id}", [
                'status' => 'COMPLETED',
            ]);

        $resCompleted->assertStatus(200)
            ->assertJsonPath('data.status', 'COMPLETED');

        $stage->refresh();
        $this->assertEquals(ProductionStageStatus::COMPLETED, $stage->status);
        $this->assertNotNull($stage->completed_at);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'action' => 'PRODUCTION_STAGE_UPDATED',
        ]);
    }

    public function test_production_staff_cannot_complete_qc_stage_via_ordinary_tracking(): void
    {
        $qcStage = ProductionStage::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'name' => 'QC',
            'sequence' => 7,
            'status' => ProductionStageStatus::PENDING,
        ]);

        $prodToken = $this->productionA->createToken('prod-token')->plainTextToken;

        // Attempt to set QC stage to COMPLETED -> 422 rejected (per Clarification 3)
        $response = $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->patchJson("/api/v1/production-stages/{$qcStage->id}", [
                'status' => 'COMPLETED',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['status']]);

        $qcStage->refresh();
        $this->assertNotEquals(ProductionStageStatus::COMPLETED, $qcStage->status);
    }

    public function test_production_update_creates_progress_snapshot(): void
    {
        // 2 stages: Cutting (COMPLETED), Assembly (IN_PROGRESS) -> progress = 50%
        ProductionStage::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'name' => 'Cutting',
            'sequence' => 1,
            'status' => ProductionStageStatus::COMPLETED,
            'is_active' => true,
        ]);

        $assemblyStage = ProductionStage::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'name' => 'Assembly',
            'sequence' => 2,
            'status' => ProductionStageStatus::IN_PROGRESS,
            'is_active' => true,
        ]);

        $prodToken = $this->productionA->createToken('prod-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson("/api/v1/production-stages/{$assemblyStage->id}/updates", [
                'description' => 'Konstruksi kaki meja dan top table sudah dirakit.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.description', 'Konstruksi kaki meja dan top table sudah dirakit.')
            ->assertJsonPath('data.progress_snapshot', 50);

        $this->assertDatabaseHas('production_updates', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'production_stage_id' => $assemblyStage->id,
            'user_id' => $this->productionA->id,
            'progress_snapshot' => 50.0,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'action' => 'PRODUCTION_UPDATE_CREATED',
        ]);
    }

    public function test_production_role_cannot_add_arbitrary_stages(): void
    {
        $prodToken = $this->productionA->createToken('prod-token')->plainTextToken;

        // Production cannot add stages -> 403
        $response = $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson("/api/v1/orders/{$this->orderA->id}/production/stages", [
                'name' => 'Custom Polish Stage',
            ]);

        $response->assertStatus(403);
    }

    public function test_cross_tenant_production_isolation_idor(): void
    {
        $customerB = Customer::factory()->create(['workshop_id' => $this->workshopB->id]);
        $orderB = Order::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'customer_id' => $customerB->id,
        ]);
        $stageB = ProductionStage::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'order_id' => $orderB->id,
            'name' => 'Cutting',
        ]);

        $tokenA = $this->ownerA->createToken('test-token')->plainTextToken;

        // User A cannot access Order B's production overview -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->getJson("/api/v1/orders/{$orderB->id}/production")
            ->assertStatus(404);

        // User A cannot update Stage B -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->patchJson("/api/v1/production-stages/{$stageB->id}", ['status' => 'IN_PROGRESS'])
            ->assertStatus(404);

        // User A cannot add update to Stage B -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson("/api/v1/production-stages/{$stageB->id}/updates", ['description' => 'Test'])
            ->assertStatus(404);
    }
}
