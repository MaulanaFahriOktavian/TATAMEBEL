<?php

namespace Tests\Feature\Qc;

use App\Enums\OrderStatus;
use App\Enums\QcDefectSeverity;
use App\Enums\QcDefectStatus;
use App\Enums\QcInspectionStatus;
use App\Enums\QcItemStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\QcDefect;
use App\Models\QcInspection;
use App\Models\QcItem;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QcInspectionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;
    private User $owner;
    private User $admin;
    private User $production;
    private User $qc;
    private Order $order;
    private OrderItem $orderItem;

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

        $this->orderItem = OrderItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'product_name' => 'Meja Makan Jati Solid',
        ]);
    }

    public function test_qc_user_can_create_inspection_with_default_checklist_template(): void
    {
        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/orders/{$this->order->id}/qc-inspections", [
                'notes' => 'Pemeriksaan akhir sebelum packing',
                'order_item_id' => $this->orderItem->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.notes', 'Pemeriksaan akhir sebelum packing')
            ->assertJsonPath('data.inspector.id', $this->qc->id);

        $inspectionId = $response->json('data.id');

        // Verify that standard 9 checklist items were instantiated with status null (unevaluated)
        $items = QcItem::where('qc_inspection_id', $inspectionId)->get();
        $this->assertCount(9, $items);

        foreach ($items as $item) {
            $this->assertNull($item->status);
        }
    }

    public function test_production_role_cannot_create_inspection(): void
    {
        $response = $this->actingAs($this->production)
            ->postJson("/api/v1/orders/{$this->order->id}/qc-inspections", [
                'notes' => 'Mencoba membuat inspeksi',
            ]);

        $response->assertStatus(403);
    }

    public function test_qc_user_can_evaluate_checklist_items(): void
    {
        $inspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::PENDING,
        ]);

        $item1 = QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'category' => 'dimension',
            'status' => null,
        ]);

        $item2 = QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'category' => 'finishing',
            'status' => null,
        ]);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$inspection->id}/items", [
                'items' => [
                    [
                        'id' => $item1->id,
                        'status' => 'PASS',
                        'notes' => 'Ukuran sesuai gambar kerja',
                    ],
                    [
                        'id' => $item2->id,
                        'status' => 'FAIL',
                        'notes' => 'Terdapat lelehan pernis pada sudut kanan',
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(QcItemStatus::PASS, $item1->fresh()->status);
        $this->assertEquals(QcItemStatus::FAIL, $item2->fresh()->status);
        $this->assertEquals('Ukuran sesuai gambar kerja', $item1->fresh()->notes);
    }

    public function test_finalize_passed_requires_all_checklist_items_to_be_evaluated(): void
    {
        $inspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::PENDING,
        ]);

        // Create 2 items: 1 passed, 1 unevaluated (status = null)
        QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcItemStatus::PASS,
        ]);

        QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => null,
        ]);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$inspection->id}/finalize", [
                'status' => 'PASSED',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_finalize_passed_rejects_if_failing_items_exist(): void
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

        QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcItemStatus::FAIL,
        ]);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$inspection->id}/finalize", [
                'status' => 'PASSED',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_finalize_passed_rejects_if_unresolved_defects_exist(): void
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

        QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcDefectStatus::OPEN,
        ]);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$inspection->id}/finalize", [
                'status' => 'PASSED',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['defects']);
    }

    public function test_finalize_passed_succeeds_when_all_items_pass_or_na_and_no_defects(): void
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

        QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcItemStatus::NA,
        ]);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$inspection->id}/finalize", [
                'status' => 'PASSED',
                'notes' => 'Kualitas sangat baik dan rapi',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'PASSED');

        $this->assertEquals(QcInspectionStatus::PASSED, $inspection->fresh()->status);
        $this->assertNotNull($inspection->fresh()->inspected_at);
    }

    public function test_finalized_inspection_is_immutable(): void
    {
        $inspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::PASSED,
            'inspected_at' => now(),
        ]);

        $item = QcItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'status' => QcItemStatus::PASS,
        ]);

        // Attempting to evaluate items on finalized inspection must fail
        $response1 = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$inspection->id}/items", [
                'items' => [
                    ['id' => $item->id, 'status' => 'FAIL'],
                ],
            ]);

        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['inspection']);

        // Attempting to finalize again must fail
        $response2 = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$inspection->id}/finalize", [
                'status' => 'REWORK',
            ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['inspection']);
    }

    public function test_re_inspection_creates_new_inspection_record_preserving_history(): void
    {
        // First inspection: marked REWORK
        $inspection1 = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::REWORK,
            'inspected_at' => now()->subDay(),
        ]);

        // Create re-inspection
        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/orders/{$this->order->id}/qc-inspections", [
                'notes' => 'Inspeksi ulang setelah perbaikan daun meja',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'PENDING');

        $this->assertEquals(2, $this->order->qcInspections()->count());
        $this->assertEquals(QcInspectionStatus::REWORK, $inspection1->fresh()->status);
    }
}
