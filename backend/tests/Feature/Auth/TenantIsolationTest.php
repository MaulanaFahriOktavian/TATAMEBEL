<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Workshop;
use App\Policies\Concerns\EnforcesWorkshopTenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshopA;
    private Workshop $workshopB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workshopA = Workshop::factory()->create([
            'name' => 'Workshop A - Kayu Jepara',
            'slug' => 'workshop-a-kayu-jepara',
        ]);

        $this->workshopB = Workshop::factory()->create([
            'name' => 'Workshop B - Mebel Klaten',
            'slug' => 'workshop-b-mebel-klaten',
        ]);

        $this->userA = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'email' => 'owner.a@kayujepara.com',
            'role' => UserRole::OWNER,
            'is_active' => true,
        ]);

        $this->userB = User::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'email' => 'owner.b@mebelklaten.com',
            'role' => UserRole::OWNER,
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_is_strictly_bound_to_own_workshop(): void
    {
        $tokenA = $this->userA->createToken('token-a')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.workshop.id', $this->workshopA->id)
            ->assertJsonPath('data.user.workshop.slug', 'workshop-a-kayu-jepara');

        // Verify Workshop B details are NOT in the response
        $this->assertNotEquals($this->workshopB->id, $response->json('data.user.workshop.id'));
        $this->assertNotEquals('workshop-b-mebel-klaten', $response->json('data.user.workshop.slug'));
    }

    public function test_tenant_isolation_boundary_prevents_cross_workshop_data_leakage(): void
    {
        // Create Customer & Order strictly for Workshop B
        $customerB = Customer::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'name' => 'Pelanggan Rahasia Workshop B',
            'phone' => '089999999999',
        ]);

        $orderB = Order::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'customer_id' => $customerB->id,
            'title' => 'Meja Makan Jati Ukir Eksklusif',
        ]);

        // Workshop A queries scoped by tenant boundary
        $scopedOrderForUserA = $this->userA->workshop
            ->orders()
            ->whereKey($orderB->id)
            ->first();

        // Must be null: User A cannot find Order B even with its known primary key
        $this->assertNull($scopedOrderForUserA);

        // Workshop B can find its own order
        $scopedOrderForUserB = $this->userB->workshop
            ->orders()
            ->whereKey($orderB->id)
            ->first();

        $this->assertNotNull($scopedOrderForUserB);
        $this->assertEquals($orderB->id, $scopedOrderForUserB->id);
    }

    public function test_enforces_workshop_tenancy_policy_trait_blocks_cross_tenant_access(): void
    {
        $policyHandler = new class {
            use EnforcesWorkshopTenancy;
        };

        $customerB = Customer::factory()->create([
            'workshop_id' => $this->workshopB->id,
        ]);

        $orderB = Order::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'customer_id' => $customerB->id,
        ]);

        // User A (even as OWNER) must be DENIED access to Workshop B's order
        $this->assertFalse(
            $policyHandler->belongsToSameWorkshop($this->userA, $orderB),
            'User A must not be allowed to access Workshop B resource.'
        );

        // User B must be ALLOWED access to its own order
        $this->assertTrue(
            $policyHandler->belongsToSameWorkshop($this->userB, $orderB),
            'User B must be allowed to access its own workshop resource.'
        );
    }

    public function test_knowing_resource_id_does_not_permit_cross_tenant_access(): void
    {
        // Attacker is OWNER or ADMIN of Workshop A
        $adminUserA = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $customerB = Customer::factory()->create([
            'workshop_id' => $this->workshopB->id,
        ]);

        $orderB = Order::factory()->create([
            'workshop_id' => $this->workshopB->id,
            'customer_id' => $customerB->id,
        ]);

        // Knowing $orderB->id (e.g. 105) does not allow query resolution through Workshop A
        $found = $adminUserA->workshop->orders()->find($orderB->id);
        $this->assertNull($found, 'Knowing resource ID must never leak cross-tenant records.');
    }

    public function test_user_role_methods_correctly_evaluate_capabilities(): void
    {
        $owner = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'role' => UserRole::OWNER,
        ]);

        $admin = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'role' => UserRole::ADMIN,
        ]);

        $production = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'role' => UserRole::PRODUCTION,
        ]);

        $qc = User::factory()->create([
            'workshop_id' => $this->workshopA->id,
            'role' => UserRole::QC,
        ]);

        // hasRole assertions
        $this->assertTrue($owner->hasRole(UserRole::OWNER));
        $this->assertFalse($owner->hasRole(UserRole::ADMIN));

        $this->assertTrue($admin->hasRole(UserRole::ADMIN));
        $this->assertFalse($admin->hasRole(UserRole::PRODUCTION));

        $this->assertTrue($production->hasRole(UserRole::PRODUCTION));
        $this->assertFalse($production->hasRole(UserRole::QC));

        $this->assertTrue($qc->hasRole(UserRole::QC));
        $this->assertFalse($qc->hasRole(UserRole::OWNER));

        // hasAnyRole assertions
        $this->assertTrue($owner->hasAnyRole([UserRole::OWNER, UserRole::ADMIN]));
        $this->assertTrue($admin->hasAnyRole([UserRole::OWNER, UserRole::ADMIN]));
        $this->assertFalse($production->hasAnyRole([UserRole::OWNER, UserRole::ADMIN]));
        $this->assertFalse($qc->hasAnyRole([UserRole::OWNER, UserRole::ADMIN]));
    }

    public function test_inactive_user_with_token_is_blocked_by_tenant_middleware(): void
    {
        $token = $this->userA->createToken('auth-token')->plainTextToken;

        // Deactivate user after token creation
        $this->userA->update(['is_active' => false]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'User account is inactive.');
    }

    public function test_user_with_deleted_or_missing_workshop_is_blocked_by_tenant_middleware(): void
    {
        $token = $this->userA->createToken('auth-token')->plainTextToken;

        // Sever workshop association without cascade-deleting user
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->userA->update(['workshop_id' => 999999]);
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'User does not belong to a valid workshop.');
    }
}
