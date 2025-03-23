<?php

namespace Tests\Feature;

use App\Models\Secret;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecretsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_secret_with_auto_generated_token(): void
    {
        $response = $this->postJson('/api/secrets', [
            'password' => 'my-secret-password'
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'url',
                'expires_at'
            ]);

        $url = $response->json('url');
        $this->assertStringContainsString(url("/api/secrets/" . Secret::first()->id . "?token="), $url);
        $this->assertDatabaseCount('secrets', 1);
    }

    public function test_can_create_secret_with_custom_passphrase(): void
    {
        $response = $this->postJson('/api/secrets', [
            'password' => 'my-secret-password',
            'passphrase' => 'my-custom-passphrase'
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'url',
                'note',
                'expires_at'
            ])
            ->assertJsonFragment([
                'url' => url("/api/secrets/" . Secret::first()->id . "?passphrase="),
                'note' => 'This secret requires the passphrase at the end of the URL to decrypt and must be provided by the recipient.'
            ]);

        $this->assertDatabaseCount('secrets', 1);
    }

    public function test_can_create_secret_with_custom_expiration(): void
    {
        $response = $this->postJson('/api/secrets', [
            'password' => 'my-secret-password',
            'expires_in' => 60 // 60 minutes
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'url',
                'expires_at'
            ]);

        $this->assertDatabaseCount('secrets', 1);
        $secret = Secret::first();
        $this->assertTrue(now()->addMinutes(59)->lt($secret->expires_at));
        $this->assertTrue(now()->addMinutes(61)->gt($secret->expires_at));
    }

    public function test_cannot_create_secret_without_password(): void
    {
        $response = $this->postJson('/api/secrets', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_cannot_create_secret_with_invalid_expiration(): void
    {
        $response = $this->postJson('/api/secrets', [
            'password' => 'my-secret-password',
            'expires_in' => Secret::MAX_TTL + 1
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['expires_in']);
    }

    public function test_can_retrieve_secret_with_token(): void
    {
        // First create a secret
        $createResponse = $this->postJson('/api/secrets', [
            'password' => 'my-secret-password'
        ]);

        $secretId = Secret::first()->id;
        $url = $createResponse->json('url');
        $token = substr($url, strpos($url, '?token=') + 7);

        // Then retrieve it using query parameters
        $response = $this->getJson("/api/secrets/{$secretId}?token={$token}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'password' => 'my-secret-password',
                'note' => 'This secret has been deleted and can no longer be retrieved.'
            ]);

        // Verify the secret was deleted after retrieval
        $this->assertDatabaseCount('secrets', 0);
    }

    public function test_can_retrieve_secret_with_passphrase(): void
    {
        // First create a secret with custom passphrase
        $createResponse = $this->postJson('/api/secrets', [
            'password' => 'my-secret-password',
            'passphrase' => 'my-custom-passphrase'
        ]);

        $secretId = Secret::first()->id;

        // Then retrieve it using query parameters
        $response = $this->getJson("/api/secrets/{$secretId}?passphrase=my-custom-passphrase");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'password' => 'my-secret-password',
                'note' => 'This secret has been deleted and can no longer be retrieved.'
            ]);

        // Verify the secret was deleted after retrieval
        $this->assertDatabaseCount('secrets', 0);
    }

    public function test_cannot_retrieve_secret_with_invalid_token(): void
    {
        // First create a secret
        $this->postJson('/api/secrets', [
            'password' => 'my-secret-password'
        ]);

        $secretId = Secret::first()->id;

        // Then try to retrieve it with invalid token
        $response = $this->getJson("/api/secrets/{$secretId}?token=invalid-token");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJsonFragment([
                'error' => 'Invalid token or passphrase'
            ]);

        // Verify the secret was not deleted
        $this->assertDatabaseCount('secrets', 1);
    }

    public function test_cannot_retrieve_secret_with_both_token_and_passphrase(): void
    {
        // First create a secret
        $this->postJson('/api/secrets', [
            'password' => 'my-secret-password'
        ]);

        $secretId = Secret::first()->id;

        // Then try to retrieve it with both token and passphrase
        $response = $this->getJson("/api/secrets/{$secretId}?token=some-token&passphrase=some-passphrase");

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment([
                'error' => 'Only one parameter (token or passphrase) should be provided, not both'
            ]);
    }

    public function test_cannot_retrieve_expired_secret(): void
    {
        // First create a secret that expires in 5 minutes (minimum allowed)
        $createResponse = $this->postJson('/api/secrets', [
            'password' => 'my-secret-password',
            'expires_in' => 5
        ]);

        $this->assertDatabaseCount('secrets', 1);
        $secret = Secret::first();
        $this->assertNotNull($secret, 'Secret should be created');

        // Manually expire the secret
        $secret->expires_at = now()->subMinute();
        $secret->save();

        // Extract token from URL
        $url = $createResponse->json('url');
        $token = substr($url, strpos($url, '?token=') + 7);

        // Then try to retrieve it
        $response = $this->getJson("/api/secrets/{$secret->id}?token={$token}");

        $response->assertStatus(Response::HTTP_GONE)
            ->assertJsonFragment([
                'error' => 'Secret has expired'
            ]);
    }

    public function test_cannot_retrieve_nonexistent_secret(): void
    {
        $response = $this->getJson('/api/secrets/non-existent-id?token=any-token');

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonFragment([
                'error' => 'Secret not found'
            ]);
    }
}
