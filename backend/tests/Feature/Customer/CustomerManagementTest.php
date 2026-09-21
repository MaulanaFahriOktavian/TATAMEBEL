<?php

namespace Tests\Feature\Customer;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshopA;
    private Workshop $workshopB;
    private User $ownerA;
    private User $adminA;
    private User $productionA;
    private User $qcA;
    private User $ownerB;

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
    }

    public function test_authenticated_user_can_list_own_customers_paginated(): void
    {
        Customer::factory()->count(3)->create(['workshop_id' => $this->workshopA->id]);
        Customer::factory()->count(2)->create(['workshop_id' => $this->workshopB->id]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/customers');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Customers retrieved successfully.')
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'company_name', 'phone', 'email', 'address', 'notes', 'created_at', 'updated_at'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_authenticated_user_can_create_customer(): void
    {
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $payload = [
            'name' => 'Budi Santoso',
            'company_name' => 'CV Furnitur Mandiri',
            'phone' => '081298765432',
            'email' => 'budi@furniturmandiri.com',
            'address' => 'Jl. Raya Kayu No. 45, Jepara',
            'notes' => 'Pelanggan tetap via WhatsApp',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/customers', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Customer created successfully.')
            ->assertJsonPath('data.name', 'Budi Santoso')
            ->assertJsonPath('data.phone', '081298765432');

        $this->assertDatabaseHas('customers', [
            'workshop_id' => $this->workshopA->id,
            'name' => 'Budi Santoso',
            'phone' => '081298765432',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'action' => 'CUSTOMER_CREATED',
            'user_id' => $this->ownerA->id,
        ]);
    }

    public function test_authenticated_user_can_view_own_customer(): void
    {
        $customer = Customer::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'name' => 'Siti Aminah',
            'phone' => '081345678901',
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/customers/'.$customer->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.name', 'Siti Aminah');
    }

    public function test_authenticated_user_can_update_own_customer(): void
    {
        $customer = Customer::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'name' => 'Pelanggan Lama',
            'phone' => '081234567890',
        ]);

        $token = $this->adminA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/customers/'.$customer->id, [
                'name' => 'Pelanggan Terupdate',
                'notes' => 'Catatan revisi',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Pelanggan Terupdate')
            ->assertJsonPath('data.notes', 'Catatan revisi');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Pelanggan Terupdate',
            'notes' => 'Catatan revisi',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'action' => 'CUSTOMER_UPDATED',
        ]);
    }

    public function test_authenticated_user_can_delete_own_customer_without_orders(): void
    {
        $customer = Customer::factory()->create([
            'workshop_id' => $this->workshopA->id,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/customers/'.$customer->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Customer deleted successfully.');

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'workshop_id' => $this->workshopA->id,
            'action' => 'CUSTOMER_DELETED',
        ]);
    }

    public function test_customer_with_existing_orders_cannot_be_deleted(): void
    {
        $customer = Customer::factory()->create([
            'workshop_id' => $this->workshopA->id,
        ]);

        Order::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'customer_id' => $customer->id,
        ]);

        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/customers/'.$customer->id);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['customer']]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_customers(): void
    {
        $this->getJson('/api/v1/customers')->assertStatus(401);
        $this->postJson('/api/v1/customers', ['name' => 'X', 'phone' => '123'])->assertStatus(401);
    }

    public function test_user_cannot_access_or_mutate_customer_of_another_workshop_idor(): void
    {
        $customerB = Customer::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'name' => 'Pelanggan Rahasia Workshop B',
            'phone' => '089999999999',
        ]);

        $tokenA = $this->ownerA->createToken('test-token')->plainTextToken;

        // View another workshop's customer -> 404 zero data leak
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->getJson('/api/v1/customers/'.$customerB->id)
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Customer not found.');

        // Update another workshop's customer -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->patchJson('/api/v1/customers/'.$customerB->id, ['name' => 'Hacked'])
            ->assertStatus(404);

        // Delete another workshop's customer -> 404
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->deleteJson('/api/v1/customers/'.$customerB->id)
            ->assertStatus(404);

        $this->assertDatabaseHas('customers', [
            'id' => $customerB->id,
            'name' => 'Pelanggan Rahasia Workshop B',
        ]);
    }

    public function test_staff_with_read_only_role_cannot_mutate_customers(): void
    {
        $customer = Customer::factory()->create([
            'workshop_id' => $this->workshopA->id,
        ]);

        $productionToken = $this->productionA->createToken('prod-token')->plainTextToken;
        $qcToken = $this->qcA->createToken('qc-token')->plainTextToken;

        // Production can read
        $this->withHeader('Authorization', 'Bearer '.$productionToken)
            ->getJson('/api/v1/customers')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$productionToken)
            ->getJson('/api/v1/customers/'.$customer->id)
            ->assertStatus(200);

        // Production cannot create
        $this->withHeader('Authorization', 'Bearer '.$productionToken)
            ->postJson('/api/v1/customers', ['name' => 'Test', 'phone' => '081111'])
            ->assertStatus(403);

        // Production cannot update
        $this->withHeader('Authorization', 'Bearer '.$productionToken)
            ->patchJson('/api/v1/customers/'.$customer->id, ['name' => 'Test'])
            ->assertStatus(403);

        // Production cannot delete
        $this->withHeader('Authorization', 'Bearer '.$productionToken)
            ->deleteJson('/api/v1/customers/'.$customer->id)
            ->assertStatus(403);

        // QC cannot create
        $this->withHeader('Authorization', 'Bearer '.$qcToken)
            ->postJson('/api/v1/customers', ['name' => 'Test', 'phone' => '081111'])
            ->assertStatus(403);
    }

    public function test_customer_validation_rules_enforced(): void
    {
        $token = $this->ownerA->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/customers', [
                'email' => 'not-an-email',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['name', 'phone', 'email'],
            ]);
    }
}
