<?php

namespace Tests\Feature\Qc;

use App\Enums\OrderStatus;
use App\Enums\QcDefectSeverity;
use App\Enums\QcDefectStatus;
use App\Enums\QcInspectionStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\QcDefect;
use App\Models\QcInspection;
use App\Models\QcItem;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;
    private User $owner;
    private User $admin;
    private User $production;
    private User $qc;
    private Order $order;
    private QcInspection $inspection;
    private QcItem $qcItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workshop = Workshop::factory()->create();

        $this->owner = User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'role' => UserRole::OWNER,
            'is_active' => true,
        ]);

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

        $this->inspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::PENDING,
        ]);

        $this->qcItem = QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $this->inspection->id,
            'category' => 'finishing',
        ]);
    }

    public function test_qc_user_can_log_defect_for_pending_inspection(): void
    {
        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$this->inspection->id}/defects", [
                'qc_item_id' => $this->qcItem->id,
                'description' => 'Terdapat goresan sepanjang 5cm pada sudut kiri meja',
                'severity' => 'MEDIUM',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.severity', 'MEDIUM')
            ->assertJsonPath('data.status', 'OPEN')
            ->assertJsonPath('data.description', 'Terdapat goresan sepanjang 5cm pada sudut kiri meja');

        $this->assertDatabaseHas('qc_defects', [
            'qc_inspection_id' => $this->inspection->id,
            'status' => 'OPEN',
            'severity' => 'MEDIUM',
        ]);
    }

    public function test_cannot_log_defect_for_finalized_inspection(): void
    {
        $this->inspection->update(['status' => QcInspectionStatus::PASSED, 'inspected_at' => now()]);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$this->inspection->id}/defects", [
                'description' => 'Mencoba tambah cacat pada inspeksi final',
                'severity' => 'LOW',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['inspection']);
    }

    public function test_production_role_can_update_defect_to_in_rework(): void
    {
        $defect = QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $this->inspection->id,
            'status' => QcDefectStatus::OPEN,
        ]);

        $response = $this->actingAs($this->production)
            ->patchJson("/api/v1/qc-defects/{$defect->id}/status", [
                'status' => 'IN_REWORK',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'IN_REWORK');

        $this->assertEquals(QcDefectStatus::IN_REWORK, $defect->fresh()->status);
    }

    public function test_production_role_can_update_defect_to_resolved_with_resolution_note(): void
    {
        $defect = QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $this->inspection->id,
            'status' => QcDefectStatus::IN_REWORK,
        ]);

        // Missing resolution must fail validation
        $failResponse = $this->actingAs($this->production)
            ->patchJson("/api/v1/qc-defects/{$defect->id}/status", [
                'status' => 'RESOLVED',
            ]);

        $failResponse->assertStatus(422)
            ->assertJsonValidationErrors(['resolution']);

        // With resolution note
        $successResponse = $this->actingAs($this->production)
            ->patchJson("/api/v1/qc-defects/{$defect->id}/status", [
                'status' => 'RESOLVED',
                'resolution' => 'Permukaan diamplas ulang grit 400 dan disemprot top coat satin ulang.',
            ]);

        $successResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'RESOLVED')
            ->assertJsonPath('data.resolution', 'Permukaan diamplas ulang grit 400 dan disemprot top coat satin ulang.');

        $this->assertEquals(QcDefectStatus::RESOLVED, $defect->fresh()->status);
        $this->assertNotNull($defect->fresh()->resolved_at);
    }

    public function test_production_and_qc_roles_cannot_mark_defect_as_accepted(): void
    {
        $defect = QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $this->inspection->id,
            'status' => QcDefectStatus::OPEN,
        ]);

        // PRODUCTION attempt
        $responseProd = $this->actingAs($this->production)
            ->patchJson("/api/v1/qc-defects/{$defect->id}/status", [
                'status' => 'ACCEPTED',
                'resolution' => 'Toleransi dari tukang',
            ]);

        $responseProd->assertStatus(403);

        // QC attempt
        $responseQc = $this->actingAs($this->qc)
            ->patchJson("/api/v1/qc-defects/{$defect->id}/status", [
                'status' => 'ACCEPTED',
                'resolution' => 'Toleransi dari inspektur',
            ]);

        $responseQc->assertStatus(403);
    }

    public function test_owner_and_admin_can_mark_defect_as_accepted_with_justification(): void
    {
        $defect = QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $this->inspection->id,
            'status' => QcDefectStatus::OPEN,
        ]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/v1/qc-defects/{$defect->id}/status", [
                'status' => 'ACCEPTED',
                'resolution' => 'Diterima sebagai variasi corak alami kayu jati atas persetujuan pemilik mebel.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'ACCEPTED');

        $this->assertEquals(QcDefectStatus::ACCEPTED, $defect->fresh()->status);
        $this->assertNotNull($defect->fresh()->resolved_at);
    }

    public function test_accepted_defect_cannot_be_reopened_or_transitioned_again(): void
    {
        $defect = QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $this->inspection->id,
            'status' => QcDefectStatus::ACCEPTED,
            'resolution' => 'Diterima oleh owner',
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/v1/qc-defects/{$defect->id}/status", [
                'status' => 'IN_REWORK',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_defect_can_be_deleted_only_while_inspection_is_pending(): void
    {
        $defect = QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $this->inspection->id,
            'status' => QcDefectStatus::OPEN,
        ]);

        // Allowed while PENDING
        $response = $this->actingAs($this->qc)
            ->deleteJson("/api/v1/qc-defects/{$defect->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('qc_defects', ['id' => $defect->id]);

        // If inspection was finalized
        $finalizedInspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'status' => QcInspectionStatus::PASSED,
        ]);

        $defect2 = QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $finalizedInspection->id,
        ]);

        $response2 = $this->actingAs($this->qc)
            ->deleteJson("/api/v1/qc-defects/{$defect2->id}");

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['defect']);
    }
}
