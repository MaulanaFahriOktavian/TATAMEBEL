<?php

namespace Tests\Feature\Media;

use App\Enums\MediaVisibility;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Media;
use App\Models\Order;
use App\Models\ProductionStage;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshopA;
    private Workshop $workshopB;
    private User $ownerA;
    private User $productionA;
    private User $ownerB;
    private Order $orderA;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->workshopA = Workshop::factory()->create();
        $this->workshopB = Workshop::factory()->create();

        $this->ownerA = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'role' => UserRole::OWNER,
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
            'status' => OrderStatus::IN_PRODUCTION,
        ]);
    }

    public function test_upload_standalone_media_evidence_for_order(): void
    {
        $file = UploadedFile::fake()->image('kayu-mentah.jpg', 800, 600);
        $prodToken = $this->productionA->createToken('prod-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson("/api/v1/orders/{$this->orderA->id}/media", [
                'file' => $file,
                'visibility' => 'CUSTOMER',
                'caption' => 'Bahan baku kayu jati gelondong baru tiba di workshop.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.original_name', 'kayu-mentah.jpg')
            ->assertJsonPath('data.visibility', 'CUSTOMER')
            ->assertJsonPath('data.caption', 'Bahan baku kayu jati gelondong baru tiba di workshop.')
            ->assertJsonPath('data.uploader.id', $this->productionA->id);

        $mediaId = $response->json('data.id');
        $media = Media::find($mediaId);

        $this->assertNotNull($media);
        $this->assertEquals($this->workshopA->id, $media->workshop_id);
        $this->assertEquals(MediaVisibility::CUSTOMER, $media->visibility);

        // Verify storage file existence and tenant pathing
        Storage::disk('public')->assertExists($media->file_path);
        $this->assertStringStartsWith("workshops/{$this->workshopA->id}/orders/{$this->orderA->id}/media/", $media->file_path);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'action' => 'MEDIA_UPLOADED',
        ]);
    }

    public function test_upload_media_attached_to_production_update(): void
    {
        $stage = ProductionStage::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'order_id' => $this->orderA->id,
            'name' => 'Assembly',
        ]);

        $file1 = UploadedFile::fake()->image('rangka-1.jpg', 1000, 800);
        $file2 = UploadedFile::fake()->image('rangka-2.jpg', 1000, 800);

        $prodToken = $this->productionA->createToken('prod-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson("/api/v1/production-stages/{$stage->id}/updates", [
                'description' => 'Pemasangan purut dan pen sambungan meja selesai.',
                'media' => [$file1, $file2],
                'media_visibility' => 'CUSTOMER',
                'media_caption' => 'Rangka meja',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.media');

        $this->assertDatabaseCount('media', 2);
        $this->assertEquals(2, $stage->order->media()->count());
    }

    public function test_media_validation_rejects_unsupported_mime_types_and_oversized_files(): void
    {
        $prodToken = $this->productionA->createToken('prod-token')->plainTextToken;

        // Rejected PDF
        $pdfFile = UploadedFile::fake()->create('dokumen.pdf', 500, 'application/pdf');
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson("/api/v1/orders/{$this->orderA->id}/media", ['file' => $pdfFile])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['file']]);

        // Rejected file > 10MB
        $hugeFile = UploadedFile::fake()->image('huge.jpg')->size(15000);
        $this->withHeader('Authorization', 'Bearer '.$prodToken)
            ->postJson("/api/v1/orders/{$this->orderA->id}/media", ['file' => $hugeFile])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['file']]);
    }

    public function test_delete_media_evidence(): void
    {
        $file = UploadedFile::fake()->image('hapus.jpg');
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $uploadRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/orders/{$this->orderA->id}/media", ['file' => $file]);

        $mediaId = $uploadRes->json('data.id');
        $filePath = $uploadRes->json('data.file_path');

        Storage::disk('public')->assertExists($filePath);

        // Delete media
        $delRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/media/{$mediaId}");

        $delRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('media', ['id' => $mediaId]);
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_cross_tenant_media_isolation_idor(): void
    {
        $customerB = Customer::factory()->create(['workshop_id' => $this->workshopB->id]);
        $orderB = Order::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'customer_id' => $customerB->id,
        ]);
        $mediaB = Media::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'order_id' => $orderB->id,
            'uploaded_by' => $this->ownerB->id,
        ]);

        $tokenA = $this->ownerA->createToken('test-token')->plainTextToken;

        // User A cannot upload to Order B -> 404
        $file = UploadedFile::fake()->image('test.jpg');
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson("/api/v1/orders/{$orderB->id}/media", ['file' => $file])
            ->assertStatus(404);

        // User A cannot view Order B media -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->getJson("/api/v1/orders/{$orderB->id}/media")
            ->assertStatus(404);

        // User A cannot delete Media B -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->deleteJson("/api/v1/media/{$mediaB->id}")
            ->assertStatus(404);
    }
}
