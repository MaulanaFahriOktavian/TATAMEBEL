<?php

namespace Tests\Feature\Shipping;

use App\Enums\OrderStatus;
use App\Enums\ShippingStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingManagementTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshopA;
    private Workshop $workshopB;
    private User $ownerA;
    private User $adminA;
    private User $prodA;
    private User $qcA;
    private User $ownerB;
    private Customer $customerA;
    private Order $orderA;
    private Order $orderB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workshopA = Workshop::factory()->create(['name' => 'Workshop Jati Lestari']);
        $this->workshopB = Workshop::factory()->create(['name' => 'Workshop Ukir Mandiri']);

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

        $this->prodA = User::factory()->create([
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

        $this->customerA = Customer::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'name' => 'Andi Pratama',
        ]);

        $this->orderA = Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $this->customerA->id,
            'order_number' => 'ORD-202609-0001',
            'status' => OrderStatus::READY_TO_SHIP,
        ]);

        $this->orderB = Order::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'order_number' => 'ORD-202609-0002',
            'status' => OrderStatus::READY_TO_SHIP,
        ]);
    }

    /**
     * 1. OWNER dapat create shipping dengan resi.
     */
    public function test_owner_can_create_shipping_with_tracking_number(): void
    {
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$this->orderA->id}/shipping", [
                'courier' => 'JNE Trucking',
                'tracking_number' => 'JNE-99887766',
                'shipping_address' => 'Jl. Pahlawan No. 10, Semarang',
                'estimated_arrival' => now()->addDays(2)->format('Y-m-d'),
                'notes' => 'Tolong kirim sebelum jam 17:00',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.courier', 'JNE Trucking')
            ->assertJsonPath('data.tracking_number', 'JNE-99887766')
            ->assertJsonPath('data.shipping_address', 'Jl. Pahlawan No. 10, Semarang')
            ->assertJsonPath('data.status', 'PENDING');

        $this->assertDatabaseHas('shipping', [
            'order_id' => $this->orderA->id,
            'workshop_id' => $this->workshopA->id,
            'courier' => 'JNE Trucking',
            'tracking_number' => 'JNE-99887766',
            'status' => ShippingStatus::PENDING->value,
        ]);
    }

    /**
     * 2. OWNER dapat create shipping tanpa tracking number (Armada Bengkel).
     */
    public function test_owner_can_create_shipping_without_tracking_number_armada_sendiri(): void
    {
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$this->orderA->id}/shipping", [
                'courier' => 'Armada Bengkel',
                'tracking_number' => null,
                'shipping_address' => 'Jl. Diponegoro No. 45, Jepara',
                'estimated_arrival' => now()->addDay()->format('Y-m-d'),
                'notes' => 'Diantar menggunakan pick-up workshop',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.courier', 'Armada Bengkel')
            ->assertJsonPath('data.tracking_number', null)
            ->assertJsonPath('data.shipping_address', 'Jl. Diponegoro No. 45, Jepara')
            ->assertJsonPath('data.status', 'PENDING');

        $this->assertDatabaseHas('shipping', [
            'order_id' => $this->orderA->id,
            'workshop_id' => $this->workshopA->id,
            'courier' => 'Armada Bengkel',
            'tracking_number' => null,
        ]);
    }

    /**
     * 3. GET sebelum shipping dibuat mengembalikan data null.
     */
    public function test_get_shipping_returns_null_before_creation(): void
    {
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/orders/{$this->orderA->id}/shipping");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null);
    }

    /**
     * 4. GET setelah create mengembalikan ShippingResource.
     */
    public function test_get_shipping_returns_shipping_resource_after_creation(): void
    {
        Shipping::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'courier' => 'Karya Jati Express',
            'tracking_number' => 'KJX-001',
            'shipping_address' => 'Jl. Kartini No. 20, Jepara',
            'status' => ShippingStatus::READY,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/orders/{$this->orderA->id}/shipping");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.courier', 'Karya Jati Express')
            ->assertJsonPath('data.tracking_number', 'KJX-001')
            ->assertJsonPath('data.status', 'READY')
            ->assertJsonPath('data.status_label', 'Siap Dikirim');
    }

    /**
     * 5. OWNER dan ADMIN dapat update shipping.
     */
    public function test_owner_and_admin_can_update_shipping(): void
    {
        $shipping = Shipping::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'courier' => 'Armada Bengkel',
            'status' => ShippingStatus::PENDING,
        ]);

        // Admin updates status to READY
        $adminToken = $this->adminA->createToken('test-token')->plainTextToken;
        $adminRes = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson("/api/v1/orders/{$this->orderA->id}/shipping", [
                'status' => ShippingStatus::READY->value,
                'notes' => 'Barang selesai packing dan siap muat',
            ]);

        $adminRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'READY')
            ->assertJsonPath('data.notes', 'Barang selesai packing dan siap muat');

        // Owner updates status to SHIPPED
        $ownerToken = $this->ownerA->createToken('test-token')->plainTextToken;
        $ownerRes = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
            ->patchJson("/api/v1/orders/{$this->orderA->id}/shipping", [
                'status' => ShippingStatus::SHIPPED->value,
            ]);

        $ownerRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'SHIPPED');

        $shipping->refresh();
        $this->assertEquals(ShippingStatus::SHIPPED, $shipping->status);
        $this->assertNotNull($shipping->shipped_at);
    }

    /**
     * 6. PRODUCTION dan QC dapat view tetapi tidak create/update.
     */
    public function test_production_and_qc_can_view_shipping_but_cannot_create_or_update(): void
    {
        $shipping = Shipping::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'courier' => 'Armada Bengkel',
            'status' => ShippingStatus::PENDING,
        ]);

        $prodToken = $this->prodA->createToken('test-token')->plainTextToken;
        $qcToken = $this->qcA->createToken('test-token')->plainTextToken;

        // View allowed
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->getJson("/api/v1/orders/{$this->orderA->id}/shipping")
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$qcToken)
            ->getJson("/api/v1/orders/{$this->orderA->id}/shipping")
            ->assertStatus(200);

        // Update forbidden (403)
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->patchJson("/api/v1/orders/{$this->orderA->id}/shipping", ['status' => 'READY'])
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer '.$qcToken)
            ->patchJson("/api/v1/orders/{$this->orderA->id}/shipping", ['status' => 'READY'])
            ->assertStatus(403);
    }

    /**
     * 7. Cross-tenant access menghasilkan 404.
     */
    public function test_cross_tenant_access_returns_404(): void
    {
        Shipping::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
        ]);

        $ownerBToken = $this->ownerB->createToken('test-token')->plainTextToken;

        // View order A shipping by workshop B owner
        $this->withHeader('Authorization', 'Bearer '.$ownerBToken)
            ->getJson("/api/v1/orders/{$this->orderA->id}/shipping")
            ->assertStatus(404);

        // Mutate order A shipping by workshop B owner
        $this->withHeader('Authorization', 'Bearer '.$ownerBToken)
            ->patchJson("/api/v1/orders/{$this->orderA->id}/shipping", ['courier' => 'Hacked Courier'])
            ->assertStatus(404);
    }

    /**
     * 8. Validation required fields menghasilkan 422.
     */
    public function test_validation_required_fields_fails_with_422(): void
    {
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$this->orderA->id}/shipping", []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['courier', 'shipping_address']);
    }

    /**
     * 9. Illegal shipping status transition ditolak.
     */
    public function test_illegal_shipping_status_transition_is_rejected(): void
    {
        Shipping::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'status' => ShippingStatus::DELIVERED,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        // DELIVERED is terminal, cannot transition back to SHIPPED
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/orders/{$this->orderA->id}/shipping", [
                'status' => ShippingStatus::SHIPPED->value,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * 10. READY_TO_SHIP -> SHIPPED tanpa shipping ditolak.
     */
    public function test_order_cannot_move_from_ready_to_ship_to_shipped_without_shipping_record(): void
    {
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        // No shipping record exists for orderA
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/orders/{$this->orderA->id}/status", [
                'status' => OrderStatus::SHIPPED->value,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->orderA->refresh();
        $this->assertEquals(OrderStatus::READY_TO_SHIP, $this->orderA->status);
    }

    /**
     * 11. READY_TO_SHIP -> SHIPPED dengan shipping berhasil.
     */
    public function test_order_transitions_to_shipped_when_shipping_exists(): void
    {
        Shipping::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'courier' => 'Armada Bengkel',
            'shipping_address' => 'Jl. Diponegoro No. 10, Jepara',
            'status' => ShippingStatus::READY,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/orders/{$this->orderA->id}/status", [
                'status' => OrderStatus::SHIPPED->value,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'SHIPPED');

        $this->orderA->refresh();
        $this->assertEquals(OrderStatus::SHIPPED, $this->orderA->status);
    }

    /**
     * 12. shipped_at otomatis terisi saat order berubah menjadi SHIPPED.
     */
    public function test_shipped_at_is_automatically_recorded_when_transitioning_to_shipped(): void
    {
        $shipping = Shipping::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'courier' => 'Armada Bengkel',
            'shipping_address' => 'Jl. Diponegoro No. 10, Jepara',
            'status' => ShippingStatus::READY,
            'shipped_at' => null,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/orders/{$this->orderA->id}/status", [
                'status' => OrderStatus::SHIPPED->value,
            ])
            ->assertStatus(200);

        $shipping->refresh();
        $this->assertEquals(ShippingStatus::SHIPPED, $shipping->status);
        $this->assertNotNull($shipping->shipped_at);
        $this->assertTrue(now()->diffInSeconds($shipping->shipped_at) < 60);
    }

    /**
     * 13. Customer Portal menampilkan shipping tanpa kebocoran data.
     */
    public function test_customer_portal_projects_shipping_without_data_leakage(): void
    {
        Shipping::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'courier' => 'Armada Bengkel',
            'tracking_number' => null,
            'shipping_address' => 'Jl. Diponegoro No. 10, Jepara',
            'notes' => 'Nomor kontak sopir: 081234567890 (internal)',
            'status' => ShippingStatus::SHIPPED,
            'shipped_at' => now(),
            'estimated_arrival' => now()->addDays(2)->toDateString(),
        ]);

        $response = $this->getJson("/api/v1/public/orders/{$this->orderA->public_token}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'shipping' => [
                        'courier',
                        'tracking_number',
                        'status',
                        'status_label',
                        'shipped_at',
                        'estimated_arrival',
                        'delivered_at',
                    ],
                ],
            ]);

        $portalData = $response->json('data');
        $shippingData = $portalData['shipping'];

        $this->assertEquals('Armada Bengkel', $shippingData['courier']);
        $this->assertNull($shippingData['tracking_number']);
        $this->assertEquals('SHIPPED', $shippingData['status']);
        $this->assertEquals('Dalam Pengiriman', $shippingData['status_label']);

        // Data security blacklist verification
        $this->assertArrayNotHasKey('id', $shippingData);
        $this->assertArrayNotHasKey('workshop_id', $shippingData);
        $this->assertArrayNotHasKey('order_id', $shippingData);
        $this->assertArrayNotHasKey('notes', $shippingData);
        $this->assertArrayNotHasKey('shipping_address', $shippingData);
        $this->assertArrayNotHasKey('public_token', $portalData['order']);
    }
}
