<?php

namespace Tests\Feature\ChangeRequest;

use App\Enums\ChangeRequestStatus;
use App\Enums\OrderStatus;
use App\Enums\SpecificationStatus;
use App\Enums\UserRole;
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Specification;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshopA;
    private Workshop $workshopB;
    private User $ownerA;
    private User $adminA;
    private User $productionA;
    private User $ownerB;
    private Order $orderA;
    private OrderItem $itemA;
    private Specification $lockedSpecA;

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
            'product_name' => 'Dipan Jati Minimalis King Size',
            'quantity' => 1,
            'unit_price' => 7500000,
            'subtotal' => 7500000,
        ]);

        $this->lockedSpecA = Specification::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_item_id' => $this->itemA->id,
            'version' => 1,
            'width' => 180.0,
            'height' => 100.0,
            'depth' => 200.0,
            'material' => 'Kayu Jati',
            'finishing' => 'Natural Doff',
            'status' => SpecificationStatus::LOCKED,
            'locked_at' => now(),
            'locked_by' => $this->ownerA->id,
        ]);
    }

    public function test_submitting_change_request_on_locked_specification_succeeds(): void
    {
        $token = $this->adminA->createToken('test-token')->plainTextToken;

        $payload = [
            'order_item_id' => $this->itemA->id,
            'requested_by' => 'Pelanggan WhatsApp (Pak Hendra)',
            'description' => 'Minta ubah lebar dipan dari 180cm menjadi 200cm (Super King) dan finishing ganti ke Walnut Glossy.',
            'reason' => 'Kamar tidur luas dan kasur yang dibeli ukuran super king.',
            'requested_changes' => [
                'width' => 200.0,
                'finishing' => 'Walnut Glossy',
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$this->orderA->id}/change-requests", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_item_id', $this->itemA->id)
            ->assertJsonPath('data.specification_id', $this->lockedSpecA->id)
            ->assertJsonPath('data.current_version', 1)
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.requested_changes.width', 200)
            ->assertJsonPath('data.requested_changes.finishing', 'Walnut Glossy');

        $this->assertDatabaseHas('change_requests', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'order_item_id' => $this->itemA->id,
            'specification_id' => $this->lockedSpecA->id,
            'current_version' => 1,
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'action' => 'CHANGE_REQUEST_CREATED',
        ]);
    }

    public function test_submitting_change_request_on_draft_specification_is_rejected(): void
    {
        // Unlock spec to DRAFT
        $this->lockedSpecA->update(['status' => SpecificationStatus::DRAFT]);

        $token = $this->adminA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$this->orderA->id}/change-requests", [
                'order_item_id' => $this->itemA->id,
                'requested_by' => 'Pelanggan',
                'description' => 'Ubah ukuran',
                'requested_changes' => ['width' => 200.0],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['order_item_id']]);
    }

    public function test_approving_change_request_creates_new_specification_version_in_draft_status(): void
    {
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        // Submit change request
        $crResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$this->orderA->id}/change-requests", [
                'order_item_id' => $this->itemA->id,
                'requested_by' => 'Pelanggan',
                'description' => 'Ubah lebar dipan menjadi 200cm',
                'requested_changes' => [
                    'width' => 200.0,
                    'finishing' => 'Walnut Brown',
                ],
            ]);

        $crId = $crResponse->json('data.id');

        // Approve change request
        $approveResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/change-requests/{$crId}/approve", [
                'review_note' => 'Disetujui. Perubahan ukuran sudah dikoordinasikan dengan supervisor kayu.',
            ]);

        $approveResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.change_request.status', 'APPROVED')
            ->assertJsonPath('data.change_request.review_note', 'Disetujui. Perubahan ukuran sudah dikoordinasikan dengan supervisor kayu.')
            // Clarification 3: Newly spawned specification version is strictly in DRAFT status
            ->assertJsonPath('data.new_specification.version', 2)
            ->assertJsonPath('data.new_specification.status', 'DRAFT')
            ->assertJsonPath('data.new_specification.width', 200)
            ->assertJsonPath('data.new_specification.finishing', 'Walnut Brown')
            // Verify other fields copied from v1
            ->assertJsonPath('data.new_specification.height', 100)
            ->assertJsonPath('data.new_specification.material', 'Kayu Jati');

        // Verify v1 in database is still LOCKED and unchanged
        $v1 = Specification::find($this->lockedSpecA->id);
        $this->assertEquals(SpecificationStatus::LOCKED, $v1->status);
        $this->assertEquals(180.0, (float) $v1->width);

        // Verify v2 exists in database in DRAFT status
        $this->assertDatabaseHas('specifications', [
            'workshop_id' => $this->workshopA->id,
            'order_item_id' => $this->itemA->id,
            'version' => 2,
            'status' => 'DRAFT',
            'width' => 200.0,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'action' => 'CHANGE_REQUEST_APPROVED',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'action' => 'SPECIFICATION_VERSION_CREATED',
        ]);
    }

    public function test_rejecting_change_request_requires_review_note_and_leaves_spec_unchanged(): void
    {
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $crResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$this->orderA->id}/change-requests", [
                'order_item_id' => $this->itemA->id,
                'requested_by' => 'Pelanggan',
                'description' => 'Minta ganti kayu ke Kayu Ebony Afrika',
                'requested_changes' => ['material' => 'Kayu Ebony Afrika'],
            ]);

        $crId = $crResponse->json('data.id');

        // Attempt rejection without review_note -> 422
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/change-requests/{$crId}/reject", [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['review_note']]);

        // Reject with review_note
        $rejectResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/change-requests/{$crId}/reject", [
                'review_note' => 'Ditolak karena bahan kayu ebony afrika tidak tersedia di workshop.',
            ]);

        $rejectResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'REJECTED')
            ->assertJsonPath('data.review_note', 'Ditolak karena bahan kayu ebony afrika tidak tersedia di workshop.');

        // No new specification version created
        $this->assertEquals(1, $this->itemA->specifications()->count());

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'action' => 'CHANGE_REQUEST_REJECTED',
        ]);
    }

    public function test_production_role_can_submit_change_request_but_cannot_approve_or_reject(): void
    {
        $prodToken = $this->productionA->createToken('prod-token')->plainTextToken;

        // Production floor staff CAN submit change request
        $response = $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson("/api/v1/orders/{$this->orderA->id}/change-requests", [
                'order_item_id' => $this->itemA->id,
                'requested_by' => 'Mandor Produksi (Budi)',
                'description' => 'Papan kayu ada mata kayu mati di bagian tengah, perlu penyesuaian ketebalan.',
                'requested_changes' => ['depth' => 205.0],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.requested_by', 'Mandor Produksi (Budi)');

        $crId = $response->json('data.id');

        // Production CANNOT approve
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson("/api/v1/change-requests/{$crId}/approve")
            ->assertStatus(403);

        // Production CANNOT reject
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson("/api/v1/change-requests/{$crId}/reject", ['review_note' => 'Ditolak'])
            ->assertStatus(403);
    }

    public function test_cross_tenant_change_request_isolation_idor(): void
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
            'status' => SpecificationStatus::LOCKED,
        ]);

        $crB = ChangeRequest::create([
            'workshop_id' => $this->workshopB->id,
            'order_id' => $orderB->id,
            'order_item_id' => $itemB->id,
            'specification_id' => $specB->id,
            'current_version' => 1,
            'requested_by' => 'Customer B',
            'description' => 'Change B',
            'requested_changes' => ['width' => 150],
            'status' => ChangeRequestStatus::PENDING,
        ]);

        $tokenA = $this->ownerA->createToken('test-token')->plainTextToken;

        // User A cannot view Change Request B -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->getJson("/api/v1/change-requests/{$crB->id}")
            ->assertStatus(404)
            ->assertJsonPath('message', 'Change request not found.');

        // User A cannot approve Change Request B -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson("/api/v1/change-requests/{$crB->id}/approve")
            ->assertStatus(404);
    }
}
