<?php

namespace Tests\Feature\Public;

use App\Enums\MediaVisibility;
use App\Enums\OrderStatus;
use App\Enums\ProductionStageStatus;
use App\Enums\QcDefectSeverity;
use App\Enums\QcDefectStatus;
use App\Enums\QcInspectionStatus;
use App\Enums\QcItemStatus;
use App\Enums\SpecificationStatus;
use App\Models\Customer;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductionStage;
use App\Models\QcDefect;
use App\Models\QcInspection;
use App\Models\QcItem;
use App\Models\Shipping;
use App\Models\Specification;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;

    private Customer $customer;

    private Order $order;

    private OrderItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');

        $this->workshop = Workshop::factory()->create([
            'name' => 'Jati Mulyo Workshop',
            'phone' => '08123456789',
            'address' => 'Jl. Pengrajin No. 10, Jepara',
        ]);

        $this->customer = Customer::factory()->create([
            'workshop_id' => $this->workshop->id,
            'name' => 'Bpk. Budi Santoso',
            'phone' => '08987654321',
            'email' => 'budi@example.com',
            'address' => 'Jl. Kenanga No. 5, Jakarta Selatan',
            'notes' => 'Catatan internal customer: pelanggan VIP repeat order.',
        ]);

        $this->order = Order::factory()->create([
            'workshop_id' => $this->workshop->id,
            'customer_id' => $this->customer->id,
            'order_number' => 'ORD-202609-0001',
            'title' => 'Meja Makan Jati 6 Kursi',
            'status' => OrderStatus::IN_PRODUCTION,
            'total_amount' => 15000000.00,
            'notes' => 'Catatan internal pesanan: berikan bonus tatakan piring kayu.',
            'public_token' => 'test-public-token-1234567890abcdefghijklmn',
            'confirmed_at' => now()->subDays(3),
        ]);

        $this->item = OrderItem::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'product_name' => 'Meja Makan Utama',
            'product_code' => 'MM-01',
            'quantity' => 1,
            'unit_price' => 10000000.00,
            'subtotal' => 10000000.00,
            'notes' => 'Finishing doff natural',
        ]);
    }

    public function test_customer_can_view_order_tracking_using_valid_public_token_without_authentication(): void
    {
        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Order tracking details retrieved successfully.')
            ->assertJsonPath('data.order.order_number', 'ORD-202609-0001')
            ->assertJsonPath('data.order.title', 'Meja Makan Jati 6 Kursi')
            ->assertJsonPath('data.order.status', 'IN_PRODUCTION')
            ->assertJsonPath('data.order.status_label', 'Sedang Diproduksi')
            ->assertJsonPath('data.order.customer_name', 'Bpk. Budi Santoso')
            ->assertJsonPath('data.order.workshop.name', 'Jati Mulyo Workshop')
            ->assertJsonPath('data.order.workshop.phone', '08123456789')
            ->assertJsonPath('data.order.workshop.address', 'Jl. Pengrajin No. 10, Jepara');
    }

    public function test_customer_tracking_returns_404_for_invalid_or_non_existent_public_token(): void
    {
        $response = $this->getJson('/api/v1/public/orders/non-existent-token-xyz');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Pesanan tidak ditemukan atau tautan pelacakan tidak valid.');
    }

    public function test_customer_tracking_response_does_not_leak_public_token(): void
    {
        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('public_token', $response->json('data.order'));
    }

    public function test_customer_tracking_strictly_excludes_internal_ids_and_tenant_workshop_id(): void
    {
        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Check root and order level
        $this->assertArrayNotHasKey('id', $data);
        $this->assertArrayNotHasKey('workshop_id', $data);
        $this->assertArrayNotHasKey('id', $data['order']);
        $this->assertArrayNotHasKey('workshop_id', $data['order']);
        $this->assertArrayNotHasKey('customer_id', $data['order']);

        // Workshop level
        $this->assertArrayNotHasKey('id', $data['order']['workshop']);
        $this->assertArrayNotHasKey('slug', $data['order']['workshop']);
        $this->assertArrayNotHasKey('timezone', $data['order']['workshop']);

        // Check internal notes
        $this->assertArrayNotHasKey('notes', $data['order']);
    }

    public function test_customer_tracking_strictly_hides_all_financial_fields(): void
    {
        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Order financial fields
        $this->assertArrayNotHasKey('total_amount', $data['order']);
        $this->assertArrayNotHasKey('payments', $data);

        // Item financial fields
        $item = $data['items'][0];
        $this->assertArrayNotHasKey('unit_price', $item);
        $this->assertArrayNotHasKey('subtotal', $item);
        $this->assertArrayNotHasKey('id', $item);
        $this->assertArrayNotHasKey('order_id', $item);
        $this->assertArrayNotHasKey('workshop_id', $item);
    }

    public function test_customer_tracking_only_displays_customer_name_and_hides_private_details(): void
    {
        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");

        $response->assertStatus(200)
            ->assertJsonPath('data.order.customer_name', 'Bpk. Budi Santoso');

        $data = $response->json('data');
        $this->assertArrayNotHasKey('customer_phone', $data['order']);
        $this->assertArrayNotHasKey('customer_email', $data['order']);
        $this->assertArrayNotHasKey('customer_address', $data['order']);
        $this->assertArrayNotHasKey('customer_notes', $data['order']);
    }

    public function test_customer_tracking_only_returns_customer_visible_media_and_excludes_internal_media(): void
    {
        // 1 Customer visible media
        $customerMedia = Media::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'visibility' => MediaVisibility::CUSTOMER,
            'caption' => 'Rangka meja utama telah dirakit kokoh.',
            'file_path' => 'workshops/1/orders/1/media/customer_photo.jpg',
        ]);

        // 1 Internal media
        $internalMedia = Media::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'visibility' => MediaVisibility::INTERNAL,
            'caption' => 'Catatan cacat internal: terdapat mata kayu di bagian bawah.',
            'file_path' => 'workshops/1/orders/1/media/internal_defect.jpg',
        ]);

        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.photos')
            ->assertJsonPath('data.photos.0.caption', 'Rangka meja utama telah dirakit kokoh.');

        $photo = $response->json('data.photos.0');
        $this->assertArrayNotHasKey('id', $photo);
        $this->assertArrayNotHasKey('file_path', $photo);
        $this->assertArrayNotHasKey('workshop_id', $photo);
        $this->assertArrayNotHasKey('order_id', $photo);
        $this->assertArrayNotHasKey('uploaded_by', $photo);
    }

    public function test_customer_tracking_only_exposes_current_locked_specification_and_excludes_draft(): void
    {
        // Version 1: LOCKED
        Specification::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_item_id' => $this->item->id,
            'version' => 1,
            'width' => 200.00,
            'height' => 75.00,
            'depth' => 100.00,
            'dimension_unit' => 'cm',
            'material' => 'Kayu Jati Solid TPK',
            'wood_grade' => 'Grade A',
            'finishing' => 'Natural Teak Oil',
            'color' => 'Warm Honey',
            'production_note' => 'Catatan teknis internal tukang: gunakan sambungan purus dobel.',
            'status' => SpecificationStatus::LOCKED,
            'locked_at' => now(),
        ]);

        // Version 2: DRAFT (in-progress change request)
        Specification::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_item_id' => $this->item->id,
            'version' => 2,
            'width' => 220.00,
            'height' => 75.00,
            'depth' => 110.00,
            'dimension_unit' => 'cm',
            'material' => 'Kayu Jati Solid Super',
            'status' => SpecificationStatus::DRAFT,
        ]);

        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.specification.version', 1)
            ->assertJsonPath('data.items.0.specification.dimensions.width', '200.00')
            ->assertJsonPath('data.items.0.specification.material', 'Kayu Jati Solid TPK');

        // Verify internal fields are not leaked
        $spec = $response->json('data.items.0.specification');
        $this->assertArrayNotHasKey('id', $spec);
        $this->assertArrayNotHasKey('production_note', $spec);
        $this->assertArrayNotHasKey('locked_by', $spec);
        $this->assertArrayNotHasKey('workshop_id', $spec);
    }

    public function test_customer_tracking_uses_authoritative_backend_progress_calculation(): void
    {
        // 4 active stages, 2 completed => 50.00%
        ProductionStage::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'name' => 'Material Preparation',
            'sequence' => 1,
            'status' => ProductionStageStatus::COMPLETED,
            'is_active' => true,
        ]);
        ProductionStage::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'name' => 'Cutting',
            'sequence' => 2,
            'status' => ProductionStageStatus::COMPLETED,
            'is_active' => true,
        ]);
        ProductionStage::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'name' => 'Assembly',
            'sequence' => 3,
            'status' => ProductionStageStatus::IN_PROGRESS,
            'is_active' => true,
        ]);
        ProductionStage::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'name' => 'Finishing',
            'sequence' => 4,
            'status' => ProductionStageStatus::PENDING,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");

        $response->assertStatus(200)
            ->assertJsonPath('data.production.progress_percentage', 50)
            ->assertJsonPath('data.production.current_stage', 'Assembly')
            ->assertJsonCount(4, 'data.production.stages')
            ->assertJsonPath('data.production.stages.0.status_label', 'Selesai')
            ->assertJsonPath('data.production.stages.2.status_label', 'Sedang Dikerjakan')
            ->assertJsonPath('data.production.stages.3.status_label', 'Menunggu');

        // Check stage internal fields
        $stage = $response->json('data.production.stages.0');
        $this->assertArrayNotHasKey('id', $stage);
        $this->assertArrayNotHasKey('order_id', $stage);
        $this->assertArrayNotHasKey('workshop_id', $stage);
    }

    public function test_customer_tracking_conceals_internal_qc_defects_and_rework_workflow(): void
    {
        $inspector = User::factory()->create(['workshop_id' => $this->workshop->id]);

        $inspection = QcInspection::create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $inspector->id,
            'status' => QcInspectionStatus::REWORK,
            'notes' => 'Catatan internal QC: goresan halus di sambungan kaki kiri.',
            'inspected_at' => now(),
        ]);

        $qcItem = QcItem::create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'order_item_id' => $this->item->id,
            'category' => 'Finishing & Coating',
            'item' => 'Kehalusan permukaan meja',
            'status' => QcItemStatus::FAIL,
            'notes' => 'Permukaan kurang halus, perlu amplas 400 dan topcoat ulang.',
        ]);

        QcDefect::create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $inspection->id,
            'qc_item_id' => $qcItem->id,
            'description' => 'Goresan serat kayu dan cat kurang merata.',
            'severity' => QcDefectSeverity::MEDIUM,
            'status' => QcDefectStatus::IN_REWORK,
            'resolution' => 'Diamplas ulang dan disemprot clear coat.',
        ]);

        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");

        $response->assertStatus(200)
            ->assertJsonPath('data.quality_control.status', 'IN_PROGRESS')
            ->assertJsonPath('data.quality_control.status_label', 'Sedang dalam Pengecekan Kualitas')
            ->assertJsonPath('data.quality_control.passed_at', null);

        $qc = $response->json('data.quality_control');
        $this->assertArrayNotHasKey('qc_defects', $qc);
        $this->assertArrayNotHasKey('defects', $qc);
        $this->assertArrayNotHasKey('notes', $qc);
        $this->assertArrayNotHasKey('rework_notes', $qc);
        $this->assertArrayNotHasKey('inspected_by', $qc);
    }

    public function test_customer_tracking_displays_passed_qc_status_when_inspection_passed(): void
    {
        $inspector = User::factory()->create(['workshop_id' => $this->workshop->id]);

        $inspection = QcInspection::create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $inspector->id,
            'status' => QcInspectionStatus::PASSED,
            'notes' => 'Catatan internal: seluruh standar mutu terpenuhi prima.',
            'inspected_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");

        $response->assertStatus(200)
            ->assertJsonPath('data.quality_control.status', 'PASSED')
            ->assertJsonPath('data.quality_control.status_label', 'Lolos Pengecekan Kualitas');
        $this->assertNotNull($response->json('data.quality_control.passed_at'));
    }

    public function test_customer_tracking_handles_order_without_production_or_shipping_gracefully(): void
    {
        $emptyOrder = Order::factory()->create([
            'workshop_id' => $this->workshop->id,
            'customer_id' => $this->customer->id,
            'order_number' => 'ORD-202609-0002',
            'title' => 'Kursi Santai',
            'status' => OrderStatus::DRAFT,
            'public_token' => 'empty-order-public-token-987654321',
        ]);

        $response = $this->getJson("/api/v1/public/orders/{$emptyOrder->public_token}");

        $response->assertStatus(200)
            ->assertJsonPath('data.order.status_label', 'Draft Pesanan')
            ->assertJsonPath('data.production.progress_percentage', 0)
            ->assertJsonPath('data.production.current_stage', null)
            ->assertJsonCount(0, 'data.production.stages')
            ->assertJsonCount(0, 'data.photos')
            ->assertJsonPath('data.shipping', null)
            ->assertJsonPath('data.quality_control.status', 'PENDING');
    }

    public function test_customer_tracking_displays_shipping_when_available(): void
    {
        Shipping::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'courier' => 'Jepara Indah Cargo',
            'tracking_number' => 'JIC-998877',
            'status' => \App\Enums\ShippingStatus::SHIPPED,
            'shipped_at' => now(),
            'notes' => 'Catatan kurir internal: supir Pak Slamet.',
        ]);

        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");

        $response->assertStatus(200)
            ->assertJsonPath('data.shipping.courier', 'Jepara Indah Cargo')
            ->assertJsonPath('data.shipping.tracking_number', 'JIC-998877')
            ->assertJsonPath('data.shipping.status', 'SHIPPED')
            ->assertJsonPath('data.shipping.status_label', 'Dalam Pengiriman');
        $this->assertNotNull($response->json('data.shipping'));

        // Check internal notes are not leaked
        $this->assertArrayNotHasKey('notes', $response->json('data.shipping'));
        $this->assertArrayNotHasKey('id', $response->json('data.shipping'));
        $this->assertArrayNotHasKey('order_id', $response->json('data.shipping'));
    }

    public function test_customer_tracking_endpoint_enforces_rate_limiting(): void
    {
        // Route has throttle:60,1
        // Perform 60 requests efficiently
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/v1/public/orders/non-existent-rate-limit-token');
            $this->assertEquals(404, $response->getStatusCode());
        }

        // 61st request should be throttled (HTTP 429)
        $rateLimitedResponse = $this->getJson('/api/v1/public/orders/non-existent-rate-limit-token');
        $this->assertEquals(429, $rateLimitedResponse->getStatusCode());
    }

    public function test_internal_media_is_physically_stored_on_private_disk_and_inaccessible_via_public_storage(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('internal_defect_photo.jpg', 800, 600);
        $user = User::factory()->create(['workshop_id' => $this->workshop->id]);

        $mediaService = app(\App\Services\MediaService::class);
        $internalMedia = $mediaService->upload(
            $this->order,
            $file,
            ['visibility' => 'INTERNAL', 'caption' => 'Bukti cacat retak internal'],
            $user
        );

        // 1. Physically stored on private disk 'local' (storage/app/private)
        Storage::disk('local')->assertExists($internalMedia->file_path);

        // 2. Physically ABSENT from public disk (storage/app/public)
        Storage::disk('public')->assertMissing($internalMedia->file_path);

        // 3. Excluded from CustomerPortalOrderResource
        $response = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");
        $response->assertStatus(200);
        $this->assertEmpty($response->json('data.photos'));

        // 4. Unauthenticated direct attempt to access storage URL without signature returns 403 Forbidden
        $directStorageResponse = $this->get("/storage/{$internalMedia->file_path}");
        $this->assertEquals(403, $directStorageResponse->getStatusCode());
    }

    public function test_qc_public_projection_scenarios_rework_and_failed_hide_internal_details(): void
    {
        $inspector = User::factory()->create(['workshop_id' => $this->workshop->id]);

        // Scenario B: REWORK inspection
        $reworkInspection = QcInspection::create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $inspector->id,
            'status' => QcInspectionStatus::REWORK,
            'notes' => 'Catatan internal: pernis gelembung di daun meja.',
            'inspected_at' => now()->subDay(),
        ]);

        $qcItem = QcItem::create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $reworkInspection->id,
            'order_item_id' => $this->item->id,
            'category' => 'Finishing & Coating',
            'item' => 'Kerapian semprotan pernis',
            'status' => QcItemStatus::FAIL,
            'notes' => 'Terdapat gelembung.',
        ]);

        QcDefect::create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $reworkInspection->id,
            'qc_item_id' => $qcItem->id,
            'description' => 'Permukaan gelembung kasar.',
            'severity' => QcDefectSeverity::HIGH,
            'status' => QcDefectStatus::OPEN,
        ]);

        $responseRework = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");
        $responseRework->assertStatus(200)
            ->assertJsonPath('data.quality_control.status', 'IN_PROGRESS')
            ->assertJsonPath('data.quality_control.status_label', 'Sedang dalam Pengecekan Kualitas')
            ->assertJsonPath('data.quality_control.passed_at', null);

        $qcRework = $responseRework->json('data.quality_control');
        $this->assertArrayNotHasKey('qc_defects', $qcRework);
        $this->assertArrayNotHasKey('defects', $qcRework);
        $this->assertArrayNotHasKey('rework_notes', $qcRework);

        // Scenario C: FAILED inspection
        $reworkInspection->update([
            'status' => QcInspectionStatus::FAILED,
            'notes' => 'Catatan internal: produk gagal uji beban.',
        ]);

        $responseFailed = $this->getJson("/api/v1/public/orders/{$this->order->public_token}");
        $responseFailed->assertStatus(200)
            ->assertJsonPath('data.quality_control.status', 'IN_PROGRESS')
            ->assertJsonPath('data.quality_control.status_label', 'Sedang dalam Pengecekan Kualitas')
            ->assertJsonPath('data.quality_control.passed_at', null);

        $qcFailed = $responseFailed->json('data.quality_control');
        $this->assertArrayNotHasKey('qc_defects', $qcFailed);
        $this->assertArrayNotHasKey('defects', $qcFailed);
        $this->assertArrayNotHasKey('notes', $qcFailed);
    }
}
