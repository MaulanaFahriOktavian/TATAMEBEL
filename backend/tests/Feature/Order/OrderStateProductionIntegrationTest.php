<?php

namespace Tests\Feature\Order;

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

class OrderStateProductionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;
    private User $owner;
    private Order $order;
    private OrderItem $item1;
    private OrderItem $item2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workshop = Workshop::factory()->create();
        $this->owner = User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'role' => UserRole::OWNER,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create(['workshop_id' => $this->workshop->id]);

        $this->order = Order::factory()->create([
            'workshop_id' => $this->workshop->id,
            'customer_id' => $customer->id,
            'status' => OrderStatus::WAITING_DP,
        ]);

        $this->item1 = OrderItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'product_name' => 'Meja Makan Jati',
        ]);

        $this->item2 = OrderItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'product_name' => 'Kursi Makan Jati',
        ]);
    }

    public function test_order_cannot_advance_to_ready_for_production_without_locked_specifications(): void
    {
        // item 1 has DRAFT spec, item 2 has no spec
        Specification::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_item_id' => $this->item1->id,
            'version' => 1,
            'status' => SpecificationStatus::DRAFT,
        ]);

        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/orders/{$this->order->id}/status", [
                'status' => 'READY_FOR_PRODUCTION',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['status']]);

        $this->order->refresh();
        $this->assertEquals(OrderStatus::WAITING_DP, $this->order->status);
    }

    public function test_order_advances_to_ready_for_production_when_all_items_have_locked_specifications(): void
    {
        // Lock specifications for both items
        Specification::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_item_id' => $this->item1->id,
            'version' => 1,
            'status' => SpecificationStatus::LOCKED,
            'locked_at' => now(),
            'locked_by' => $this->owner->id,
        ]);

        Specification::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_item_id' => $this->item2->id,
            'version' => 1,
            'status' => SpecificationStatus::LOCKED,
            'locked_at' => now(),
            'locked_by' => $this->owner->id,
        ]);

        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Verify no production stages exist yet
        $this->assertEquals(0, $this->order->productionStages()->count());

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/orders/{$this->order->id}/status", [
                'status' => 'READY_FOR_PRODUCTION',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'READY_FOR_PRODUCTION');

        $this->order->refresh();
        $this->assertEquals(OrderStatus::READY_FOR_PRODUCTION, $this->order->status);

        // Verify production stages were automatically initialized (default 8 stages per Decision 4 Option A)
        $this->assertEquals(8, $this->order->productionStages()->count());

        // Verify subsequent transition to IN_PRODUCTION succeeds
        $inProdResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/orders/{$this->order->id}/status", [
                'status' => 'IN_PRODUCTION',
            ]);

        $inProdResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'IN_PRODUCTION');
    }
}
