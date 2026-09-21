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

class SpecificationVersioningTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;
    private User $owner;
    private Order $order;
    private OrderItem $item;

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
            'status' => OrderStatus::DRAFT,
        ]);

        $this->item = OrderItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'product_name' => 'Meja Rias Retro Teak',
            'product_code' => 'MR-01',
            'quantity' => 1,
            'unit_price' => 2800000,
            'subtotal' => 2800000,
        ]);
    }

    public function test_order_item_can_have_multiple_specification_versions_ordered(): void
    {
        $v1 = Specification::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_item_id' => $this->item->id,
            'version' => 1,
            'status' => SpecificationStatus::LOCKED,
            'material' => 'Kayu Jati Grade B',
            'locked_at' => now()->subDay(),
            'locked_by' => $this->owner->id,
        ]);

        $v2 = Specification::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_item_id' => $this->item->id,
            'version' => 2,
            'status' => SpecificationStatus::DRAFT,
            'material' => 'Kayu Jati Grade A',
        ]);

        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/orders/{$this->order->id}/items/{$this->item->id}/specifications");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.version', 2)
            ->assertJsonPath('data.0.status', 'DRAFT')
            ->assertJsonPath('data.1.version', 1)
            ->assertJsonPath('data.1.status', 'LOCKED');
    }

    public function test_current_specification_strictly_resolves_highest_locked_version(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        // Case 1: Only v1 exists and it is DRAFT -> current is null
        $v1 = Specification::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_item_id' => $this->item->id,
            'version' => 1,
            'status' => SpecificationStatus::DRAFT,
        ]);

        $res1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/orders/{$this->order->id}/items/{$this->item->id}/specifications/current");
        $res1->assertStatus(200)
            ->assertJsonPath('data', null);

        // Case 2: v1 is LOCKED, v2 is DRAFT -> current is strictly v1
        $v1->update([
            'status' => SpecificationStatus::LOCKED,
            'locked_at' => now(),
            'locked_by' => $this->owner->id,
        ]);

        $v2 = Specification::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_item_id' => $this->item->id,
            'version' => 2,
            'status' => SpecificationStatus::DRAFT,
            'material' => 'Material Baru v2',
        ]);

        $res2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/orders/{$this->order->id}/items/{$this->item->id}/specifications/current");
        $res2->assertStatus(200)
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.status', 'LOCKED');

        // Case 3: v2 is now LOCKED -> current is strictly v2
        $v2->update([
            'status' => SpecificationStatus::LOCKED,
            'locked_at' => now(),
            'locked_by' => $this->owner->id,
        ]);

        $res3 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/orders/{$this->order->id}/items/{$this->item->id}/specifications/current");
        $res3->assertStatus(200)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.status', 'LOCKED')
            ->assertJsonPath('data.material', 'Material Baru v2');
    }

    public function test_historical_versions_remain_immutable_when_new_versions_are_created(): void
    {
        $v1 = Specification::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_item_id' => $this->item->id,
            'version' => 1,
            'status' => SpecificationStatus::LOCKED,
            'width' => 120.0,
            'finishing' => 'Melamine Gloss',
            'locked_at' => now(),
        ]);

        $v2 = Specification::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_item_id' => $this->item->id,
            'version' => 2,
            'status' => SpecificationStatus::DRAFT,
            'width' => 140.0,
            'finishing' => 'Natural PU Satin',
        ]);

        // Verify v1 in database is completely intact
        $v1Fresh = Specification::find($v1->id);
        $this->assertEquals(1, $v1Fresh->version);
        $this->assertEquals(SpecificationStatus::LOCKED, $v1Fresh->status);
        $this->assertEquals(120.0, (float) $v1Fresh->width);
        $this->assertEquals('Melamine Gloss', $v1Fresh->finishing);

        // Verify v2 exists separately
        $this->assertEquals(2, $v2->version);
        $this->assertEquals(140.0, (float) $v2->width);
    }
}
