<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_token(): void
    {
        $invitation = Invitation::factory()->create([
            'phone_number' => '5511999999999',
            'role' => UserRole::Client,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'client_id' => $this->passwordGrantClient->getKey(),
            'client_secret' => $this->passwordGrantClient->plainSecret,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'phone_number', 'role', 'status'],
                'access_token',
                'refresh_token',
                'expires_in',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'phone_number' => '5511999999999',
            'role' => 'CLIENT',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_registration_marks_invitation_as_used(): void
    {
        $invitation = Invitation::factory()->create();

        $this->postJson('/api/v1/auth/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'email' => 'mark-used@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'client_id' => $this->passwordGrantClient->getKey(),
            'client_secret' => $this->passwordGrantClient->plainSecret,
        ]);

        $this->assertNotNull($invitation->fresh()->used_at);
    }

    public function test_registration_fails_with_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'token' => str_repeat('x', 64),
            'name' => 'New User',
            'email' => 'invalid-token@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'client_id' => $this->passwordGrantClient->getKey(),
            'client_secret' => $this->passwordGrantClient->plainSecret,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    public function test_registration_fails_with_expired_token(): void
    {
        $invitation = Invitation::factory()->expired()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'email' => 'expired@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'client_id' => $this->passwordGrantClient->getKey(),
            'client_secret' => $this->passwordGrantClient->plainSecret,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    public function test_registration_fails_with_used_token(): void
    {
        $invitation = Invitation::factory()->used()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'email' => 'used@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'client_id' => $this->passwordGrantClient->getKey(),
            'client_secret' => $this->passwordGrantClient->plainSecret,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    public function test_registration_restores_soft_deleted_user(): void
    {
        $user = User::factory()->create([
            'email' => 'deleted@example.com',
            'phone_number' => '5511444444444',
            'role' => UserRole::Client,
            'status' => UserStatus::Active,
        ]);
        $user->delete();

        $invitation = Invitation::factory()->create([
            'phone_number' => '5511333333333',
            'role' => UserRole::Admin,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'token' => $invitation->token,
            'name' => 'Restored User',
            'email' => 'deleted@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'client_id' => $this->passwordGrantClient->getKey(),
            'client_secret' => $this->passwordGrantClient->plainSecret,
        ]);

        $response->assertCreated();

        $restoredUser = User::where('email', 'deleted@example.com')->first();
        $this->assertNotNull($restoredUser);
        $this->assertNull($restoredUser->deleted_at);
        $this->assertEquals('Restored User', $restoredUser->name);
        $this->assertEquals('5511333333333', $restoredUser->phone_number);
        $this->assertEquals(UserRole::Admin, $restoredUser->role);
    }

    public function test_registration_requires_name_and_password(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'token' => $invitation->token,
            'client_id' => $this->passwordGrantClient->getKey(),
            'client_secret' => $this->passwordGrantClient->plainSecret,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'email' => 'confirm@example.com',
            'password' => 'password123',
            'client_id' => $this->passwordGrantClient->getKey(),
            'client_secret' => $this->passwordGrantClient->plainSecret,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_client_credentials(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'email' => 'creds@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['client_id', 'client_secret']);
    }

    public function test_registration_requires_email(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'client_id' => $this->passwordGrantClient->getKey(),
            'client_secret' => $this->passwordGrantClient->plainSecret,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_uses_phone_number_from_invitation(): void
    {
        $invitation = Invitation::factory()->create([
            'phone_number' => '5511888887777',
            'role' => UserRole::Client,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'email' => 'phone-from-invite@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'client_id' => $this->passwordGrantClient->getKey(),
            'client_secret' => $this->passwordGrantClient->plainSecret,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'phone-from-invite@example.com',
            'phone_number' => '5511888887777',
        ]);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $invitation = Invitation::factory()->create();

        $response = $this->postJson('/api/v1/auth/register', [
            'token' => $invitation->token,
            'name' => 'New User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'client_id' => $this->passwordGrantClient->getKey(),
            'client_secret' => $this->passwordGrantClient->plainSecret,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
