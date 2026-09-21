<?php

namespace Tests\Feature\Qc;

use App\Enums\OrderStatus;
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

class TenantIsolationQcTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshopA;
    private Workshop $workshopB;
    private User $qcA;
    private User $qcB;
    private Order $orderA;
    private QcInspection $inspectionA;
    private QcDefect $defectA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workshopA = Workshop::factory()->create();
        $this->workshopB = Workshop::factory()->create();

        $this->qcA = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'role' => UserRole::QC,
            'is_active' => true,
        ]);

        $this->qcB = User::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'role' => UserRole::QC,
            'is_active' => true,
        ]);

        $customerA = Customer::factory()->create(['workshop_id' => $this->workshopA->id]);

        $this->orderA = Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $customerA->id,
            'status' => OrderStatus::QC,
        ]);

        $this->inspectionA = QcInspection::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'inspected_by' => $this->qcA->id,
            'status' => QcInspectionStatus::PENDING,
        ]);

        $this->defectA = QcDefect::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'qc_inspection_id' => $this->inspectionA->id,
            'status' => QcDefectStatus::OPEN,
        ]);
    }

    public function test_cross_tenant_cannot_view_or_list_inspections(): void
    {
        // Workshop B tries to list Workshop A's order inspections -> 404
        $responseList = $this->actingAs($this->qcB)
            ->getJson("/api/v1/orders/{$this->orderA->id}/qc-inspections");

        $responseList->assertStatus(404);

        // Workshop B tries to view Workshop A's inspection directly -> 404
        $responseShow = $this->actingAs($this->qcB)
            ->getJson("/api/v1/qc-inspections/{$this->inspectionA->id}");

        $responseShow->assertStatus(404);
    }

    public function test_cross_tenant_cannot_evaluate_or_finalize_inspection(): void
    {
        $item = QcItem::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'qc_inspection_id' => $this->inspectionA->id,
        ]);

        $responseEval = $this->actingAs($this->qcB)
            ->postJson("/api/v1/qc-inspections/{$this->inspectionA->id}/items", [
                'items' => [['id' => $item->id, 'status' => 'PASS']],
            ]);

        $responseEval->assertStatus(404);

        $responseFinalize = $this->actingAs($this->qcB)
            ->postJson("/api/v1/qc-inspections/{$this->inspectionA->id}/finalize", [
                'status' => 'PASSED',
            ]);

        $responseFinalize->assertStatus(404);
    }

    public function test_cross_tenant_cannot_log_defect_on_other_workshop_inspection(): void
    {
        $response = $this->actingAs($this->qcB)
            ->postJson("/api/v1/qc-inspections/{$this->inspectionA->id}/defects", [
                'description' => 'Mencoba log defect cross-tenant',
                'severity' => 'LOW',
            ]);

        $response->assertStatus(404);
    }

    public function test_cross_tenant_cannot_update_or_delete_other_workshop_defect(): void
    {
        $responsePatch = $this->actingAs($this->qcB)
            ->patchJson("/api/v1/qc-defects/{$this->defectA->id}/status", [
                'status' => 'IN_REWORK',
            ]);

        $responsePatch->assertStatus(404);

        $responseDelete = $this->actingAs($this->qcB)
            ->deleteJson("/api/v1/qc-defects/{$this->defectA->id}");

        $responseDelete->assertStatus(404);
    }
}
