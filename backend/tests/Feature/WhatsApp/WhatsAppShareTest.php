<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppShareTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;

    private Workshop $otherWorkshop;

    private User $owner;

    private User $admin;

    private User $production;

    private User $qc;

    private Customer $customer;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workshop = Workshop::factory()->create([
            'name' => 'Karya Jati Jepara',
        ]);

        $this->otherWorkshop = Workshop::factory()->create([
            'name' => 'Bengkel Kayu Lain',
        ]);

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

        $this->customer = Customer::factory()->create([
            'workshop_id' => $this->workshop->id,
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
        ]);

        $this->order = Order::factory()->create([
            'workshop_id' => $this->workshop->id,
            'customer_id' => $this->customer->id,
            'order_number' => 'ORD-202609-0001',
            'status' => OrderStatus::CONFIRMED,
            'title' => 'Meja Makan Minimalis',
        ]);

        OrderItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'product_name' => 'Meja Makan Jati 6 Kursi',
            'quantity' => 1,
            'unit_price' => 5000000,
            'subtotal' => 5000000,
        ]);
    }

    public function test_owner_can_generate_whatsapp_share_data(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'phone',
                    'message',
                    'url',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('6281234567890', $data['phone']);
        $this->assertStringContainsString('https://wa.me/6281234567890?text=', $data['url']);
        $this->assertStringContainsString('Budi Santoso', $data['message']);
        $this->assertStringContainsString('ORD-202609-0001', $data['message']);
        $this->assertStringContainsString('Meja Makan Jati 6 Kursi', $data['message']);
        $this->assertStringContainsString('/track/'.$this->order->public_token, $data['message']);
    }

    public function test_admin_can_generate_whatsapp_share_data(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(200);
        $this->assertEquals('6281234567890', $response->json('data.phone'));
    }

    public function test_production_role_is_forbidden_to_generate_whatsapp_data(): void
    {
        $response = $this->actingAs($this->production, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(403);
    }

    public function test_qc_role_is_forbidden_to_generate_whatsapp_data(): void
    {
        $response = $this->actingAs($this->qc, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(401);
    }

    public function test_cross_tenant_order_access_returns_404(): void
    {
        $otherOrder = Order::factory()->create([
            'workshop_id' => $this->otherWorkshop->id,
            'order_number' => 'ORD-202609-9999',
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/orders/{$otherOrder->id}/whatsapp");

        $response->assertStatus(404);
    }

    public function test_normalizes_customer_phone_number_properly(): void
    {
        $this->customer->update(['phone' => '+62 812-9988-7766']);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(200);
        $this->assertEquals('6281299887766', $response->json('data.phone'));
        $this->assertStringStartsWith('https://wa.me/6281299887766?text=', $response->json('data.url'));
    }

    public function test_invalid_or_missing_customer_phone_returns_422(): void
    {
        $this->customer->update(['phone' => 'tidak-ada-nomor']);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_response_data_does_not_leak_public_token_as_standalone_field(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('public_token', $response->json('data'));
        $this->assertArrayNotHasKey('order_id', $response->json('data'));
        $this->assertArrayNotHasKey('workshop_id', $response->json('data'));
        $this->assertArrayNotHasKey('customer_id', $response->json('data'));
    }

    public function test_message_strictly_excludes_financial_and_internal_data(): void
    {
        $this->order->update(['notes' => 'Catatan rahasia bengkel internal']);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(200);
        $message = $response->json('data.message');

        $this->assertStringNotContainsString('5000000', $message);
        $this->assertStringNotContainsString('5.000.000', $message);
        $this->assertStringNotContainsString('Rp', $message);
        $this->assertStringNotContainsString('Catatan rahasia bengkel internal', $message);
    }

    public function test_status_mapping_order_created_template(): void
    {
        $this->order->update(['status' => OrderStatus::CONFIRMED]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(200);
        $message = $response->json('data.message');
        $this->assertStringContainsString('Pesanan Anda telah dicatat oleh Karya Jati Jepara.', $message);
        $this->assertStringContainsString('Nomor Pesanan: ORD-202609-0001', $message);
        $this->assertStringContainsString('Produk: Meja Makan Jati 6 Kursi', $message);
    }

    public function test_status_mapping_in_production_template(): void
    {
        $this->order->update(['status' => OrderStatus::IN_PRODUCTION]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(200);
        $message = $response->json('data.message');
        $this->assertStringContainsString('sudah masuk tahap produksi.', $message);
        $this->assertStringContainsString('Pantau proses pengerjaan:', $message);
        $this->assertStringNotContainsString('telah dicatat oleh', $message);
    }

    public function test_status_mapping_generic_progress_template_for_qc_and_subsequent_stages(): void
    {
        foreach ([OrderStatus::QC, OrderStatus::PACKING, OrderStatus::READY_TO_SHIP, OrderStatus::SHIPPED] as $stageStatus) {
            $this->order->update(['status' => $stageStatus]);

            $response = $this->actingAs($this->owner, 'sanctum')
                ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

            $response->assertStatus(200);
            $message = $response->json('data.message');
            $this->assertStringContainsString('sedang dalam proses pengerjaan.', $message);
            $this->assertStringNotContainsString('telah dicatat oleh', $message);
            $this->assertStringNotContainsString('sudah masuk tahap produksi', $message);
        }
    }

    public function test_status_mapping_completed_template(): void
    {
        $this->order->update(['status' => OrderStatus::COMPLETED]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(200);
        $message = $response->json('data.message');
        $this->assertStringContainsString('telah selesai diproses.', $message);
        $this->assertStringContainsString('Detail dan perkembangan pesanan:', $message);
    }

    public function test_cancelled_orders_cannot_generate_whatsapp_share_data(): void
    {
        $this->order->update(['status' => OrderStatus::CANCELLED]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order']);
    }

    public function test_records_audit_log_without_sensitive_message_or_phone(): void
    {
        $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->id}/whatsapp");

        $log = ActivityLog::where('action', 'WHATSAPP_SHARE_GENERATED')
            ->where('order_id', $this->order->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->workshop->id, $log->workshop_id);
        $this->assertEquals($this->owner->id, $log->user_id);
        $this->assertEquals(['order_id' => $this->order->id, 'channel' => 'whatsapp'], $log->metadata);

        // Crucial security check: full message, phone, and tokens must NOT be stored in audit log
        $this->assertStringNotContainsString('6281234567890', json_encode($log->metadata));
        $this->assertStringNotContainsString('http', json_encode($log->metadata));
        $this->assertStringNotContainsString($this->order->public_token, json_encode($log->metadata));
    }
}
