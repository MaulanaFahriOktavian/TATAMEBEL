<?php

namespace Tests\Feature\Qc;

use App\Enums\MediaVisibility;
use App\Enums\OrderStatus;
use App\Enums\QcDefectStatus;
use App\Enums\QcInspectionStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Media;
use App\Models\Order;
use App\Models\QcDefect;
use App\Models\QcInspection;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QcMediaEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;
    private User $qc;
    private Order $order;
    private QcInspection $inspection;
    private QcDefect $defect;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->workshop = Workshop::factory()->create();

        $this->qc = User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'role' => UserRole::QC,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create(['workshop_id' => $this->workshop->id]);

        $this->order = Order::factory()->create([
            'workshop_id' => $this->workshop->id,
            'customer_id' => $customer->id,
            'status' => OrderStatus::QC,
        ]);

        $this->inspection = QcInspection::factory()->create([
            'workshop_id' => $this->workshop->id,
            'order_id' => $this->order->id,
            'inspected_by' => $this->qc->id,
            'status' => QcInspectionStatus::PENDING,
        ]);

        $this->defect = QcDefect::factory()->create([
            'workshop_id' => $this->workshop->id,
            'qc_inspection_id' => $this->inspection->id,
            'status' => QcDefectStatus::OPEN,
        ]);
    }

    public function test_qc_user_can_upload_inspection_photo_evidence(): void
    {
        $file = UploadedFile::fake()->image('inspection_passed.jpg', 1200, 800);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$this->inspection->id}/media", [
                'file' => $file,
                'caption' => 'Foto bukti hasil akhir produk',
                'visibility' => 'CUSTOMER',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.visibility', 'CUSTOMER')
            ->assertJsonPath('data.caption', 'Foto bukti hasil akhir produk');

        $mediaId = $response->json('data.id');
        $media = Media::find($mediaId);

        $this->assertNotNull($media);
        $this->assertEquals($this->inspection->id, $media->qc_inspection_id);
        $this->assertEquals(MediaVisibility::CUSTOMER, $media->visibility);
        Storage::disk('public')->assertExists($media->file_path);
    }

    public function test_qc_user_can_upload_defect_photo_evidence_default_internal(): void
    {
        $file = UploadedFile::fake()->image('scratch_defect.jpg', 800, 600);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-defects/{$this->defect->id}/media", [
                'file' => $file,
                'caption' => 'Foto detail goresan pernis',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.visibility', 'INTERNAL');

        $mediaId = $response->json('data.id');
        $media = Media::find($mediaId);

        $this->assertNotNull($media);
        $this->assertEquals($this->defect->id, $media->qc_defect_id);
        $this->assertEquals($this->inspection->id, $media->qc_inspection_id);
        $this->assertEquals(MediaVisibility::INTERNAL, $media->visibility);
        Storage::disk('public')->assertExists($media->file_path);
    }

    public function test_cannot_upload_inspection_media_to_finalized_inspection(): void
    {
        $this->inspection->update(['status' => QcInspectionStatus::PASSED, 'inspected_at' => now()]);

        $file = UploadedFile::fake()->image('after_final.jpg', 800, 600);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-inspections/{$this->inspection->id}/media", [
                'file' => $file,
                'caption' => 'Foto tambahan pada inspeksi final',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['inspection']);
    }

    public function test_cannot_upload_defect_media_to_defect_on_finalized_inspection(): void
    {
        $this->inspection->update(['status' => QcInspectionStatus::PASSED, 'inspected_at' => now()]);

        $file = UploadedFile::fake()->image('defect_after_final.jpg', 800, 600);

        $response = $this->actingAs($this->qc)
            ->postJson("/api/v1/qc-defects/{$this->defect->id}/media", [
                'file' => $file,
                'caption' => 'Foto defect tambahan pada inspeksi final',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['defect']);
    }
}
