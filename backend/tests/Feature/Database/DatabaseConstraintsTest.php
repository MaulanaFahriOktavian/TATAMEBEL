<?php

namespace Tests\Feature\Database;

use App\Enums\OrderStatus;
use App\Enums\ShippingStatus;
use App\Enums\SpecificationStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipping;
use App\Models\Specification;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test unique(workshop_id, order_number) constraint.
     */
    public function test_unique_workshop_id_and_order_number_constraint(): void
    {
        $workshop = Workshop::factory()->create();
        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);

        Order::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-00001',
            'title' => 'Meja Makan Jati',
            'status' => OrderStatus::DRAFT,
            'public_token' => Str::random(64),
        ]);

        // Attempt duplicate order_number within the SAME workshop should fail
        $this->expectException(QueryException::class);

        Order::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-00001',
            'title' => 'Kursi Santai',
            'status' => OrderStatus::DRAFT,
            'public_token' => Str::random(64),
        ]);
    }

    /**
     * Test that different workshops CAN share the same order_number.
     */
    public function test_same_order_number_allowed_across_different_workshops(): void
    {
        $workshop1 = Workshop::factory()->create();
        $customer1 = Customer::factory()->create(['workshop_id' => $workshop1->id]);

        $workshop2 = Workshop::factory()->create();
        $customer2 = Customer::factory()->create(['workshop_id' => $workshop2->id]);

        $order1 = Order::create([
            'workshop_id' => $workshop1->id,
            'customer_id' => $customer1->id,
            'order_number' => 'ORD-99999',
            'title' => 'Order Workshop 1',
            'status' => OrderStatus::DRAFT,
            'public_token' => Str::random(64),
        ]);

        $order2 = Order::create([
            'workshop_id' => $workshop2->id,
            'customer_id' => $customer2->id,
            'order_number' => 'ORD-99999',
            'title' => 'Order Workshop 2',
            'status' => OrderStatus::DRAFT,
            'public_token' => Str::random(64),
        ]);

        $this->assertEquals('ORD-99999', $order1->order_number);
        $this->assertEquals('ORD-99999', $order2->order_number);
        $this->assertNotEquals($order1->workshop_id, $order2->workshop_id);
    }

    /**
     * Test unique public_token constraint.
     */
    public function test_unique_public_token_constraint(): void
    {
        $workshop = Workshop::factory()->create();
        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);
        $sharedToken = Str::random(64);

        Order::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-10001',
            'title' => 'Order 1',
            'status' => OrderStatus::DRAFT,
            'public_token' => $sharedToken,
        ]);

        $this->expectException(QueryException::class);

        Order::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'order_number' => 'ORD-10002',
            'title' => 'Order 2',
            'status' => OrderStatus::DRAFT,
            'public_token' => $sharedToken,
        ]);
    }

    /**
     * Test unique(order_item_id, version) on specifications table (Correction #2).
     */
    public function test_unique_specification_version_per_order_item(): void
    {
        $workshop = Workshop::factory()->create();
        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);
        $order = Order::factory()->create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
        ]);
        $item = OrderItem::factory()->create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
        ]);

        Specification::create([
            'workshop_id' => $workshop->id,
            'order_item_id' => $item->id,
            'version' => 1,
            'status' => SpecificationStatus::DRAFT,
            'material' => 'Kayu Jati',
        ]);

        // Attempting to insert duplicate version 1 for same order_item must throw QueryException
        $this->expectException(QueryException::class);

        Specification::create([
            'workshop_id' => $workshop->id,
            'order_item_id' => $item->id,
            'version' => 1,
            'status' => SpecificationStatus::LOCKED,
            'material' => 'Kayu Mahoni',
        ]);
    }

    /**
     * Test unique(order_id) on shipping table (Correction #3: Order hasOne Shipping).
     */
    public function test_unique_order_id_on_shipping_table(): void
    {
        $workshop = Workshop::factory()->create();
        $customer = Customer::factory()->create(['workshop_id' => $workshop->id]);
        $order = Order::factory()->create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
        ]);

        Shipping::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'courier' => 'Ekspedisi Jepara Cargo',
            'shipping_address' => 'Jl. Merdeka No. 45, Jakarta',
            'status' => ShippingStatus::PENDING,
        ]);

        // Second shipping record for the SAME order must fail at database level
        $this->expectException(QueryException::class);

        Shipping::create([
            'workshop_id' => $workshop->id,
            'order_id' => $order->id,
            'courier' => 'Lalamove',
            'shipping_address' => 'Alamat Lain',
            'status' => ShippingStatus::PENDING,
        ]);
    }

    /**
     * Test unique workshops.slug constraint.
     */
    public function test_unique_workshop_slug_constraint(): void
    {
        Workshop::create([
            'name' => 'Workshop A',
            'slug' => 'workshop-unik',
            'phone' => '0811111111',
        ]);

        $this->expectException(QueryException::class);

        Workshop::create([
            'name' => 'Workshop B',
            'slug' => 'workshop-unik',
            'phone' => '0822222222',
        ]);
    }

    /**
     * Test unique users.email constraint.
     */
    public function test_unique_user_email_constraint(): void
    {
        $workshop = Workshop::factory()->create();

        User::factory()->create([
            'workshop_id' => $workshop->id,
            'email' => 'duplicate@example.com',
        ]);

        $this->expectException(QueryException::class);

        User::factory()->create([
            'workshop_id' => $workshop->id,
            'email' => 'duplicate@example.com',
        ]);
    }
}
