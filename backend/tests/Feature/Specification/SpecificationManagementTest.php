<?php

namespace Tests\Feature\Specification;

use App\Enums\OrderStatus;
use App\Enums\SpecificationStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Specification;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecificationManagementTest extends TestCase
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
    private OrderItem $itemA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workshopA = Workshop::factory()->create([
            'name' => 'Workshop Kayu Jepara',
            'slug' => 'workshop-kayu-jepara',
        ]);

        $this->workshopB = Workshop::factory()->create([
            'name' => 'Workshop Mebel Klaten',
            'slug' => 'workshop-mebel-klaten',
        ]);

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
            'status' => OrderStatus::DRAFT,
        ]);

        $this->itemA = OrderItem::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'product_name' => 'Meja Makan Solid Teak 200x90',
            'product_code' => 'MM-01',
            'quantity' => 1,
            'unit_price' => 4500000,
            'subtotal' => 4500000,
        ]);
    }

    public function test_owner_and_admin_can_create_draft_specification_for_order_item(): void
    {
        $token = $this->adminA->createToken('test-token')->plainTextToken;

        $payload = [
            'width' => 200.0,
            'height' => 75.0,
            'depth' => 90.0,
            'dimension_unit' => 'cm',
            'material' => 'Kayu Jati Solid TPK Perhutani',
            'wood_grade' => 'Grade A',
            'finishing' => 'Natural PU Satin',
            'color' => 'Warm Teak',
            'fabric' => null,
            'design_reference' => 'https://example.com/sketches/meja-makan.jpg',
            'special_request' => 'Sudut meja dibuat beveled 45 derajat',
            'production_note' => 'Gunakan konstruksi mortise and tenon ganda',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$this->orderA->id}/items/{$this->itemA->id}/specifications", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.product_name', 'Meja Makan Solid Teak 200x90')
            ->assertJsonPath('data.product_code', 'MM-01')
            ->assertJsonPath('data.quantity', 1)
            ->assertJsonPath('data.width', 200)
            ->assertJsonPath('data.height', 75)
            ->assertJsonPath('data.depth', 90)
            ->assertJsonPath('data.material', 'Kayu Jati Solid TPK Perhutani')
            ->assertJsonPath('data.locked_at', null)
            ->assertJsonPath('data.locked_by', null);

        $this->assertDatabaseHas('specifications', [
            'workshop_id' => $this->workshopA->id,
            'order_item_id' => $this->itemA->id,
            'version' => 1,
            'status' => 'DRAFT',
            'material' => 'Kayu Jati Solid TPK Perhutani',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'action' => 'SPECIFICATION_CREATED',
        ]);
    }

    public function test_cannot_create_multiple_draft_specifications_simultaneously(): void
    {
        Specification::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_item_id' => $this->itemA->id,
            'version' => 1,
            'status' => SpecificationStatus::DRAFT,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$this->orderA->id}/items/{$this->itemA->id}/specifications", [
                'material' => 'Kayu Mahoni',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['order_item_id']]);
    }

    public function test_owner_and_admin_can_update_draft_specification(): void
    {
        $spec = Specification::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_item_id' => $this->itemA->id,
            'version' => 1,
            'status' => SpecificationStatus::DRAFT,
            'material' => 'Kayu Jati',
            'color' => 'Natural',
        ]);

        $token = $this->adminA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/specifications/{$spec->id}", [
                'color' => 'Walnut Brown Dark',
                'special_request' => 'Tambahkan lubang kabel di tengah meja',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.color', 'Walnut Brown Dark')
            ->assertJsonPath('data.special_request', 'Tambahkan lubang kabel di tengah meja');

        $spec->refresh();
        $this->assertEquals('Walnut Brown Dark', $spec->color);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'action' => 'SPECIFICATION_UPDATED',
        ]);
    }

    public function test_owner_and_admin_can_lock_specification(): void
    {
        $spec = Specification::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_item_id' => $this->itemA->id,
            'version' => 1,
            'status' => SpecificationStatus::DRAFT,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/specifications/{$spec->id}/lock");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'LOCKED')
            ->assertJsonPath('data.locked_by.id', $this->ownerA->id);

        $spec->refresh();
        $this->assertEquals(SpecificationStatus::LOCKED, $spec->status);
        $this->assertNotNull($spec->locked_at);
        $this->assertEquals($this->ownerA->id, $spec->locked_by);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'action' => 'SPECIFICATION_LOCKED',
        ]);
    }

    public function test_direct_update_on_locked_specification_is_rejected(): void
    {
        $spec = Specification::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_item_id' => $this->itemA->id,
            'version' => 1,
            'status' => SpecificationStatus::LOCKED,
            'locked_at' => now(),
            'locked_by' => $this->ownerA->id,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/specifications/{$spec->id}", [
                'color' => 'Black Doff',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['specification']]);

        // Verify data was NOT changed
        $spec->refresh();
        $this->assertNotEquals('Black Doff', $spec->color);
    }

    public function test_cross_tenant_specification_access_returns_404_idor(): void
    {
        $customerB = Customer::factory()->create(['workshop_id' => $this->workshopB->id]);
        $orderB = Order::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'customer_id' => $customerB->id,
        ]);
        $itemB = OrderItem::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'order_id' => $orderB->id,
        ]);
        $specB = Specification::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'order_item_id' => $itemB->id,
            'status' => SpecificationStatus::DRAFT,
        ]);

        $tokenA = $this->ownerA->createToken('test-token')->plainTextToken;

        // View another workshop's spec -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->getJson("/api/v1/specifications/{$specB->id}")
            ->assertStatus(404)
            ->assertJsonPath('message', 'Specification not found.');

        // Update another workshop's spec -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->patchJson("/api/v1/specifications/{$specB->id}", ['color' => 'Red'])
            ->assertStatus(404);

        // Lock another workshop's spec -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson("/api/v1/specifications/{$specB->id}/lock")
            ->assertStatus(404);

        // Create spec for another workshop's order item -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson("/api/v1/orders/{$orderB->id}/items/{$itemB->id}/specifications", ['material' => 'Kayu'])
            ->assertStatus(404);
    }

    public function test_production_and_qc_roles_cannot_create_update_or_lock_specifications(): void
    {
        $spec = Specification::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_item_id' => $this->itemA->id,
            'version' => 1,
            'status' => SpecificationStatus::DRAFT,
        ]);

        $prodToken = $this->productionA->createToken('prod-token')->plainTextToken;
        $qcToken = $this->qcA->createToken('qc-token')->plainTextToken;

        // Production can VIEW specifications (read-only for floor)
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->getJson("/api/v1/specifications/{$spec->id}")
            ->assertStatus(200);

        // QC can VIEW specifications (read-only for inspection)
        $this->withHeader('Authorization', 'Bearer '.$qcToken)
            ->getJson("/api/v1/specifications/{$spec->id}")
            ->assertStatus(200);

        // Production CANNOT create spec
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson("/api/v1/orders/{$this->orderA->id}/items/{$this->itemA->id}/specifications", ['material' => 'Kayu'])
            ->assertStatus(403);

        // Production CANNOT update spec
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->patchJson("/api/v1/specifications/{$spec->id}", ['material' => 'Kayu Baru'])
            ->assertStatus(403);

        // Production CANNOT lock spec
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson("/api/v1/specifications/{$spec->id}/lock")
            ->assertStatus(403);

        // QC CANNOT create, update, or lock spec
        $this->withHeader('Authorization', 'Bearer '.$qcToken)
            ->patchJson("/api/v1/specifications/{$spec->id}", ['material' => 'Kayu Baru'])
            ->assertStatus(403);
    }
}
