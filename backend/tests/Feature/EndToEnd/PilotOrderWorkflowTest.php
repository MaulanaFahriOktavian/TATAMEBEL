<?php

namespace Tests\Feature\EndToEnd;

use App\Enums\OrderStatus;
use App\Enums\ProductionStageStatus;
use App\Enums\QcInspectionStatus;
use App\Enums\QcItemStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\ProductionStage;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilotOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_end_to_end_pilot_order_workflow(): void
    {
        // 1. Setup Workshop and Owner
        $workshop = Workshop::factory()->create([
            'name' => 'Karya Jati Pilot Workshop',
            'phone' => '08123456789',
            'address' => 'Jl. Pengrajin Mebel No. 1, Jepara',
        ]);

        $owner = User::factory()->create([
            'workshop_id' => $workshop->id,
            'name' => 'Pak Joko Owner',
            'email' => 'owner.pilot@tatamebel.com',
            'password' => bcrypt('Secret123!'),
            'role' => UserRole::OWNER,
            'is_active' => true,
        ]);

        // Step 1: Login Owner
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'owner.pilot@tatamebel.com',
            'password' => 'Secret123!',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure(['data' => ['token', 'user']]);
        $token = $loginResponse->json('data.token');
        $headers = ['Authorization' => "Bearer {$token}"];

        // Step 2: Create Customer
        $customerResponse = $this->postJson('/api/v1/customers', [
            'name' => 'Ibu Ratna Sari',
            'phone' => '081987654321',
            'email' => 'ratna.sari@example.com',
            'address' => 'Jl. Melati Indah No. 5, Semarang',
        ], $headers);

        $customerResponse->assertStatus(201);
        $customerId = $customerResponse->json('data.id');

        // Step 3 & 4: Create Order with Items
        $orderResponse = $this->postJson('/api/v1/orders', [
            'customer_id' => $customerId,
            'title' => 'Set Meja Makan Scandinavian Jati',
            'notes' => 'Permintaan finishing natural matte',
            'items' => [
                [
                    'product_name' => 'Meja Makan Jati 180x90',
                    'product_code' => 'MMJ-01',
                    'quantity' => 1,
                    'unit_price' => 6500000,
                    'notes' => 'Kayu jati TPK perhutani',
                ],
            ],
        ], $headers);

        $orderResponse->assertStatus(201);
        $orderId = $orderResponse->json('data.id');
        $orderNumber = $orderResponse->json('data.order_number');
        $publicToken = $orderResponse->json('data.public_token');
        $itemId = $orderResponse->json('data.items.0.id');

        $this->assertNotEmpty($orderNumber);
        $this->assertNotEmpty($publicToken);
        $this->assertEquals(OrderStatus::DRAFT->value, $orderResponse->json('data.status'));

        // Step 5: Create Draft Technical Specification
        $specResponse = $this->postJson("/api/v1/orders/{$orderId}/items/{$itemId}/specifications", [
            'wood_type' => 'Jati TPK Perhutani',
            'dimensions' => '180 x 90 x 75 cm',
            'finishing' => 'Natural Matte Polyurethane',
            'color' => 'Warm Honey Teak',
            'special_instructions' => 'Sudut meja dibuat round bevel 2mm',
        ], $headers);

        $specResponse->assertStatus(201);
        $specId = $specResponse->json('data.id');
        $this->assertEquals('DRAFT', $specResponse->json('data.status'));

        // Step 6: Lock Technical Specification
        $lockResponse = $this->postJson("/api/v1/specifications/{$specId}/lock", [], $headers);
        $lockResponse->assertStatus(200);
        $this->assertEquals('LOCKED', $lockResponse->json('data.status'));

        // Step 7: Transition Order State: DRAFT -> CONFIRMED -> WAITING_DP -> READY_FOR_PRODUCTION
        $this->patchJson("/api/v1/orders/{$orderId}/status", ['status' => OrderStatus::CONFIRMED->value], $headers)->assertStatus(200);
        $this->patchJson("/api/v1/orders/{$orderId}/status", ['status' => OrderStatus::WAITING_DP->value], $headers)->assertStatus(200);
        $this->patchJson("/api/v1/orders/{$orderId}/status", ['status' => OrderStatus::READY_FOR_PRODUCTION->value], $headers)->assertStatus(200);

        // Step 8: Start Production: READY_FOR_PRODUCTION -> IN_PRODUCTION
        $inProdResponse = $this->patchJson("/api/v1/orders/{$orderId}/status", ['status' => OrderStatus::IN_PRODUCTION->value], $headers);
        $inProdResponse->assertStatus(200);

        // Verify 8 default production stages initialized
        $stages = ProductionStage::where('order_id', $orderId)->orderBy('sequence')->get();
        $this->assertCount(8, $stages);

        // Step 9: Advance Production Stages 1 through 6 (Material Preparation -> Finishing)
        $qcUser = User::factory()->create([
            'workshop_id' => $workshop->id,
            'role' => UserRole::QC,
            'is_active' => true,
        ]);
        $qcHeaders = ['Authorization' => 'Bearer '.$qcUser->createToken('qc-test')->plainTextToken];

        $prodUser = User::factory()->create([
            'workshop_id' => $workshop->id,
            'role' => UserRole::PRODUCTION,
            'is_active' => true,
        ]);
        $prodHeaders = ['Authorization' => 'Bearer '.$prodUser->createToken('prod-test')->plainTextToken];

        for ($i = 0; $i < 6; $i++) {
            $stage = $stages[$i];
            $this->patchJson("/api/v1/production-stages/{$stage->id}", [
                'status' => ProductionStageStatus::IN_PROGRESS->value,
            ], $prodHeaders)->assertStatus(200);

            $this->patchJson("/api/v1/production-stages/{$stage->id}", [
                'status' => ProductionStageStatus::COMPLETED->value,
            ], $prodHeaders)->assertStatus(200);
        }

        // Step 10: Transition Order to QC: IN_PRODUCTION -> QC
        $this->patchJson("/api/v1/orders/{$orderId}/status", ['status' => OrderStatus::QC->value], $headers)->assertStatus(200);

        // Step 11: Create QC Inspection (Initializes 9 checklist items and moves QC Stage Sequence 7 to IN_PROGRESS)
        $qcStage = ProductionStage::where('order_id', $orderId)->where('sequence', 7)->first();
        $this->assertEquals(ProductionStageStatus::PENDING, $qcStage->status);

        $inspectionResponse = $this->postJson("/api/v1/orders/{$orderId}/qc-inspections", [
            'notes' => 'Pemeriksaan akhir pra-kemasan meja makan',
        ], $qcHeaders);

        $inspectionResponse->assertStatus(201);
        $inspectionId = $inspectionResponse->json('data.id');
        $this->assertEquals(QcInspectionStatus::PENDING->value, $inspectionResponse->json('data.status'));

        // Verify QC Stage is now automatically IN_PROGRESS
        $qcStage->refresh();
        $this->assertEquals(ProductionStageStatus::IN_PROGRESS, $qcStage->status);

        // Step 12: Evaluate Checklist Items
        $items = $inspectionResponse->json('data.items');
        $this->assertCount(9, $items);

        $evaluations = array_map(fn ($item) => [
            'id' => $item['id'],
            'status' => QcItemStatus::PASS->value,
            'notes' => 'Sesuai standar mutu Jepara',
        ], $items);

        $this->postJson("/api/v1/qc-inspections/{$inspectionId}/items", [
            'items' => $evaluations,
        ], $qcHeaders)->assertStatus(200);

        // Step 13: Finalize Inspection as PASSED
        $finalizeResponse = $this->postJson("/api/v1/qc-inspections/{$inspectionId}/finalize", [
            'status' => QcInspectionStatus::PASSED->value,
            'notes' => 'Lolos seluruh kriteria mutu',
        ], $qcHeaders);

        $finalizeResponse->assertStatus(200);
        $this->assertEquals(QcInspectionStatus::PASSED->value, $finalizeResponse->json('data.status'));

        // Step 14: Verify QC Production Stage is automatically COMPLETED
        $qcStage->refresh();
        $this->assertEquals(ProductionStageStatus::COMPLETED, $qcStage->status);
        $this->assertNotNull($qcStage->completed_at);

        // Step 15: Move Order to PACKING (Gate Verified)
        $packingResponse = $this->patchJson("/api/v1/orders/{$orderId}/status", [
            'status' => OrderStatus::PACKING->value,
        ], $headers);
        $packingResponse->assertStatus(200);
        $this->assertEquals(OrderStatus::PACKING->value, $packingResponse->json('data.status'));

        // Step 16, 17, 18: Public Customer Portal Verification
        $portalResponse = $this->getJson("/api/v1/public/orders/{$publicToken}");
        $portalResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'order' => [
                        'order_number',
                        'title',
                        'status',
                        'status_label',
                        'customer_name',
                        'created_at',
                        'workshop',
                    ],
                    'items',
                    'production' => [
                        'progress_percentage',
                        'stages',
                    ],
                    'quality_control' => [
                        'status',
                        'status_label',
                        'passed_at',
                    ],
                ],
            ]);

        // Security check on public portal payload
        $portalData = $portalResponse->json('data');
        $this->assertArrayNotHasKey('public_token', $portalData['order']);
        $this->assertArrayNotHasKey('total_amount', $portalData['order']);
        $this->assertArrayNotHasKey('unit_price', $portalData['items'][0]);
        $this->assertArrayNotHasKey('notes', $portalData['order']);
        $this->assertArrayNotHasKey('qc_defects', $portalData['quality_control']);
        $this->assertEquals('Lolos Pengecekan Kualitas', $portalData['quality_control']['status_label']);

        // Step 19 & 20: WhatsApp Share Generation & Verification
        $whatsAppResponse = $this->getJson("/api/v1/orders/{$orderId}/whatsapp", $headers);
        $whatsAppResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'phone',
                    'message',
                    'url',
                ],
            ]);

        $waData = $whatsAppResponse->json('data');
        $this->assertEquals('6281987654321', $waData['phone']);
        $this->assertStringStartsWith('https://wa.me/6281987654321?text=', $waData['url']);
        $this->assertStringContainsString('Ibu Ratna Sari', $waData['message']);
        $this->assertStringContainsString($orderNumber, $waData['message']);
        $this->assertStringContainsString("/track/{$publicToken}", $waData['message']);

        // Verify Audit Log
        $audit = ActivityLog::where('action', 'WHATSAPP_SHARE_GENERATED')->where('order_id', $orderId)->first();
        $this->assertNotNull($audit);
        $this->assertEquals(['order_id' => $orderId, 'channel' => 'whatsapp'], $audit->metadata);
    }
}
