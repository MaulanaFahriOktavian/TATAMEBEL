<?php

namespace Tests\Feature\Order;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshopA;
    private Workshop $workshopB;
    private User $ownerA;
    private User $adminA;
    private User $productionA;
    private User $qcA;
    private User $ownerB;
    private Customer $customerA;
    private Customer $customerB;

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

        $this->customerA = Customer::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'name' => 'Pelanggan Workshop A',
            'phone' => '081234567890',
        ]);

        $this->customerB = Customer::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'name' => 'Pelanggan Workshop B',
            'phone' => '089876543210',
        ]);
    }

    public function test_authenticated_user_can_list_own_orders_paginated(): void
    {
        Order::factory()->count(2)->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $this->customerA->id,
        ]);

        Order::factory()->count(3)->create([
            'workshop_id' => $this->workshopB->id,
            'customer_id' => $this->customerB->id,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Orders retrieved successfully.')
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'order_number', 'title', 'status', 'total_amount', 'notes', 'public_token', 'created_at', 'customer'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_authenticated_user_can_create_order_with_items_and_backend_calculations(): void
    {
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $payload = [
            'customer_id' => $this->customerA->id,
            'title' => 'Set Meja Makan Minimalis Jati',
            'notes' => 'Pesanan via WhatsApp',
            'items' => [
                [
                    'product_name' => 'Meja Makan Solid Teak 200x90',
                    'product_code' => 'MM-01',
                    'quantity' => 1,
                    'unit_price' => 4500000,
                    'notes' => 'Finishing Natural PU',
                ],
                [
                    'product_name' => 'Kursi Makan Jati',
                    'product_code' => 'KM-02',
                    'quantity' => 6,
                    'unit_price' => 750000,
                    'notes' => 'Bantalan fabric cream',
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Order created successfully.')
            ->assertJsonPath('data.title', 'Set Meja Makan Minimalis Jati')
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.total_amount', 9000000); // 1*4.5m + 6*750k = 9,000,000

        $orderData = $response->json('data');
        $this->assertNotEmpty($orderData['public_token']);
        $this->assertMatchesRegularExpression('/^ORD-\d{6}-\d{4}$/', $orderData['order_number']);
        $this->assertCount(2, $orderData['items']);
        $this->assertEquals(4500000, $orderData['items'][0]['subtotal']);
        $this->assertEquals(4500000, $orderData['items'][1]['subtotal']);

        // Assert database persistence
        $this->assertDatabaseHas('orders', [
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $this->customerA->id,
            'total_amount' => 9000000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'workshop_id' => $this->workshopA->id,
            'product_name' => 'Meja Makan Solid Teak 200x90',
            'subtotal' => 4500000,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'action' => 'ORDER_CREATED',
            'user_id' => $this->ownerA->id,
        ]);
    }

    public function test_order_creation_requires_at_least_one_item(): void
    {
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders', [
                'customer_id' => $this->customerA->id,
                'title' => 'Pesanan Tanpa Item',
                'items' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['items']]);
    }

    public function test_order_cannot_link_to_customer_of_another_workshop(): void
    {
        $tokenA = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/v1/orders', [
                'customer_id' => $this->customerB->id, // Customer belongs to Workshop B
                'title' => 'Cross-tenant illegal order',
                'items' => [
                    [
                        'product_name' => 'Item X',
                        'quantity' => 1,
                        'unit_price' => 100000,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['customer_id']]);

        $this->assertDatabaseMissing('orders', [
            'title' => 'Cross-tenant illegal order',
        ]);
    }

    public function test_authenticated_user_can_view_own_order_detail(): void
    {
        $order = Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $this->customerA->id,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/orders/'.$order->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.customer.id', $this->customerA->id);
    }

    public function test_user_cannot_view_or_change_status_of_another_workshop_order_idor(): void
    {
        $orderB = Order::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'customer_id' => $this->customerB->id,
        ]);

        $tokenA = $this->ownerA->createToken('test-token')->plainTextToken;

        // View another workshop's order -> 404 zero data leak
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->getJson('/api/v1/orders/'.$orderB->id)
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Order not found.');

        // Change status of another workshop's order -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->patchJson('/api/v1/orders/'.$orderB->id.'/status', ['status' => 'QUOTATION'])
            ->assertStatus(404);
    }

    public function test_order_status_transition_state_machine_valid_paths(): void
    {
        $order = Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $this->customerA->id,
            'status' => OrderStatus::DRAFT,
        ]);

        $token = $this->adminA->createToken('test-token')->plainTextToken;

        // Sequential progression: DRAFT -> QUOTATION -> CONFIRMED -> WAITING_DP -> READY_FOR_PRODUCTION -> IN_PRODUCTION -> QC -> PACKING -> READY_TO_SHIP -> SHIPPED -> COMPLETED
        $validSequence = [
            'QUOTATION',
            'CONFIRMED',
            'WAITING_DP',
            'READY_FOR_PRODUCTION',
            'IN_PRODUCTION',
            'QC',
            'PACKING',
            'READY_TO_SHIP',
            'SHIPPED',
            'COMPLETED',
        ];

        foreach ($validSequence as $targetStatus) {
            if ($targetStatus === 'PACKING') {
                \App\Models\QcInspection::factory()->create([
                    'workshop_id' => $this->workshopA->id,
                    'order_id' => $order->id,
                    'status' => \App\Enums\QcInspectionStatus::PASSED,
                    'inspected_at' => now(),
                ]);
            }

            $response = $this->withHeader('Authorization', 'Bearer '.$token)
                ->patchJson('/api/v1/orders/'.$order->id.'/status', ['status' => $targetStatus]);

            $response->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.status', $targetStatus);
        }

        $order->refresh();
        $this->assertEquals(OrderStatus::COMPLETED, $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertNotNull($order->completed_at);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $order->id,
            'action' => 'ORDER_STATUS_CHANGED',
        ]);
    }

    public function test_order_status_direct_transition_draft_to_confirmed(): void
    {
        $order = Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $this->customerA->id,
            'status' => OrderStatus::DRAFT,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        // Direct transition DRAFT -> CONFIRMED (WhatsApp fast-track)
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/orders/'.$order->id.'/status', ['status' => 'CONFIRMED']);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'CONFIRMED');

        $order->refresh();
        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);
        $this->assertNotNull($order->confirmed_at);
    }

    public function test_order_status_cancellation_allowed_from_early_states(): void
    {
        // Cancel from DRAFT
        $orderDraft = Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $this->customerA->id,
            'status' => OrderStatus::DRAFT,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/orders/'.$orderDraft->id.'/status', ['status' => 'CANCELLED'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'CANCELLED');

        $orderDraft->refresh();
        $this->assertNotNull($orderDraft->cancelled_at);

        // Cancel from IN_PRODUCTION
        $orderInProd = Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $this->customerA->id,
            'status' => OrderStatus::IN_PRODUCTION,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/orders/'.$orderInProd->id.'/status', ['status' => 'CANCELLED'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'CANCELLED');

        $orderInProd->refresh();
        $this->assertNotNull($orderInProd->cancelled_at);
    }

    public function test_order_status_cancellation_forbidden_from_post_production_states(): void
    {
        $forbiddenStates = [
            OrderStatus::QC,
            OrderStatus::PACKING,
            OrderStatus::READY_TO_SHIP,
            OrderStatus::SHIPPED,
            OrderStatus::COMPLETED,
        ];

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        foreach ($forbiddenStates as $forbiddenState) {
            $order = Order::factory()->create([
                'workshop_id' => $this->workshopA->id,
                'customer_id' => $this->customerA->id,
                'status' => $forbiddenState,
            ]);

            $response = $this->withHeader('Authorization', 'Bearer '.$token)
                ->patchJson('/api/v1/orders/'.$order->id.'/status', ['status' => 'CANCELLED']);

            $response->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonStructure(['success', 'message', 'errors' => ['status']]);

            $order->refresh();
            $this->assertEquals($forbiddenState, $order->status);
        }
    }

    public function test_invalid_arbitrary_status_transitions_are_rejected(): void
    {
        $order = Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $this->customerA->id,
            'status' => OrderStatus::DRAFT,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        // DRAFT -> SHIPPED (invalid skip)
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/orders/'.$order->id.'/status', ['status' => 'SHIPPED'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // COMPLETED -> DRAFT (cannot reverse completed order)
        $completedOrder = Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $this->customerA->id,
            'status' => OrderStatus::COMPLETED,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/orders/'.$completedOrder->id.'/status', ['status' => 'DRAFT'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_staff_with_read_only_roles_cannot_create_or_change_order_status(): void
    {
        $order = Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $this->customerA->id,
            'status' => OrderStatus::DRAFT,
        ]);

        $prodToken = $this->productionA->createToken('prod-token')->plainTextToken;
        $qcToken = $this->qcA->createToken('qc-token')->plainTextToken;

        // Production can view
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->getJson('/api/v1/orders')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->getJson('/api/v1/orders/'.$order->id)
            ->assertStatus(200);

        // Production cannot create
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson('/api/v1/orders', [
                'customer_id' => $this->customerA->id,
                'title' => 'Denied order',
                'items' => [['product_name' => 'Item', 'quantity' => 1, 'unit_price' => 100]],
            ])
            ->assertStatus(403);

        // Production cannot change status
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->patchJson('/api/v1/orders/'.$order->id.'/status', ['status' => 'QUOTATION'])
            ->assertStatus(403);

        // QC cannot change status
        $this->withHeader('Authorization', 'Bearer '.$qcToken)
            ->patchJson('/api/v1/orders/'.$order->id.'/status', ['status' => 'QUOTATION'])
            ->assertStatus(403);
    }
}
