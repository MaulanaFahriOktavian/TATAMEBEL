<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workshop = Workshop::factory()->create([
            'name' => 'Workshop Kayu Lestari',
            'slug' => 'workshop-kayu-lestari',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'email' => 'owner@kayulestari.com',
            'password' => Hash::make('password'),
            'role' => UserRole::OWNER,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@kayulestari.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'owner@kayulestari.com')
            ->assertJsonPath('data.user.role', 'OWNER')
            ->assertJsonPath('data.user.workshop.id', $this->workshop->id)
            ->assertJsonPath('data.user.workshop.slug', 'workshop-kayu-lestari');

        $this->assertNotEmpty($response->json('data.token'));

        // Verify zero leakage of sensitive attributes
        $userData = $response->json('data.user');
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'email' => 'owner@kayulestari.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@kayulestari.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials.');

        $this->assertNull($response->json('data'));
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@tatamebel.local',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_fails_for_inactive_user_without_enumerating_status(): void
    {
        User::factory()->inactive()->create([
            'workshop_id' => $this->workshop->id,
            'email' => 'inactive@kayulestari.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@kayulestari.com',
            'password' => 'password',
        ]);

        // Security rule: inactive user response must be identical to invalid credentials (anti-enumeration)
        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_fails_if_user_has_no_workshop(): void
    {
        $user = User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'email' => 'noworkshop@kayulestari.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Sever workshop association without cascade-deleting user
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $user->update(['workshop_id' => 999999]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'noworkshop@kayulestari.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'User does not belong to a valid workshop.');
    }

    public function test_login_validation_errors_return_standard_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['email', 'password'],
            ]);
    }

    public function test_authenticated_user_can_retrieve_identity_via_me(): void
    {
        $user = User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'name' => 'Pak Bambang',
            'email' => 'owner@kayulestari.com',
            'role' => UserRole::OWNER,
            'is_active' => true,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Authenticated user.')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', 'Pak Bambang')
            ->assertJsonPath('data.user.email', 'owner@kayulestari.com')
            ->assertJsonPath('data.user.role', 'OWNER')
            ->assertJsonPath('data.user.is_active', true)
            ->assertJsonPath('data.user.workshop.id', $this->workshop->id)
            ->assertJsonPath('data.user.workshop.name', 'Workshop Kayu Lestari');

        // Sensitive fields check
        $this->assertArrayNotHasKey('password', $response->json('data.user'));
        $this->assertArrayNotHasKey('remember_token', $response->json('data.user'));
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_user_can_logout_and_revoke_current_token(): void
    {
        $user = User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'is_active' => true,
        ]);

        $token = $user->createToken('active-token')->plainTextToken;

        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logout successful.')
            ->assertJsonPath('data', null);

        // Verify token was revoked from database
        $this->assertDatabaseEmpty('personal_access_tokens');

        // Reset auth guard cache between requests in same test
        $this->app['auth']->forgetGuards();

        // Subsequent call with revoked token must return 401
        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_logout_only_revokes_current_token_leaving_other_tokens_active(): void
    {
        $user = User::factory()->create([
            'workshop_id' => $this->workshop->id,
            'is_active' => true,
        ]);

        $tokenDevice1 = $user->createToken('device-1')->plainTextToken;
        $tokenDevice2 = $user->createToken('device-2')->plainTextToken;

        // Logout from Device 1
        $this->withHeader('Authorization', 'Bearer '.$tokenDevice1)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Reset auth guard cache
        $this->app['auth']->forgetGuards();

        // Device 1 token is revoked
        $this->withHeader('Authorization', 'Bearer '.$tokenDevice1)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);

        $this->app['auth']->forgetGuards();

        // Device 2 token remains valid
        $this->withHeader('Authorization', 'Bearer '.$tokenDevice2)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200);
    }
}
