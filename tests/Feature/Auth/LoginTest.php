<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Login Tests - Bypass Verification
|--------------------------------------------------------------------------
|
| Test login behavior with and without bypass verification config.
| The bypass is controlled by:
|   - BYPASS_EMAIL_VERIFICATION_WEB  (middleware for web)
|   - BYPASS_EMAIL_VERIFICATION_API  (API responses)
|
*/

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'role' => 'user',
        'affiliate_code' => strtoupper(substr(md5(uniqid('test@example.com', true)), 0, 8)),
    ]);
});

it('returns access token on successful login', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'user' => ['id', 'name', 'email'],
        ])
        ->assertJson([
            'token_type' => 'Bearer',
            'user' => [
                'email' => 'test@example.com',
            ],
        ]);
});

it('returns 421 on wrong password', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(421);
});

it('returns 422 on missing fields', function () {
    $response = $this->postJson('/api/login', [
        'email' => '',
        'password' => '',
    ]);

    $response->assertStatus(422);
});

/*
|--------------------------------------------------------------------------
| Login - Unverified User + Bypass OFF
|--------------------------------------------------------------------------
*/

it('returns needs_verification for unverified user when bypass is OFF', function () {
    config(['langkahkecil.bypass.email_verification.api' => false]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $gateway = config('langkahkecil.verification.gateway', 'email');

    $response->assertStatus(200)
        ->assertJson([
            'needs_verification' => true,
            'verification_gateway' => $gateway,
        ])
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'needs_verification',
            'verification_gateway',
            'message',
            'user',
        ]);
});

/*
|--------------------------------------------------------------------------
| Login - Unverified User + Bypass ON
|--------------------------------------------------------------------------
*/

it('returns full response for unverified user when bypass is ON', function () {
    config(['langkahkecil.bypass.email_verification.api' => true]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonMissing(['needs_verification' => true])
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'user',
        ]);

    // Should NOT contain needs_verification
    $json = $response->json();
    expect(array_key_exists('needs_verification', $json))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Login - Verified User (bypass doesn't matter)
|--------------------------------------------------------------------------
*/

it('returns full response for verified user regardless of bypass', function () {
    config(['langkahkecil.bypass.email_verification.api' => false]);

    $this->user->update(['verified_at' => now()]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonMissing(['needs_verification' => true])
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'user',
        ]);
});

/*
|--------------------------------------------------------------------------
| Register Tests
|--------------------------------------------------------------------------
*/

it('returns needs_verification false on register when bypass is ON', function () {
    config(['langkahkecil.bypass.email_verification.api' => true]);
    config(['langkahkecil.verification.register_backend' => false]);

    $response = $this->postJson('/api/register', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'needs_verification' => false,
            'message' => 'Akun berhasil dibuat.',
        ]);
});

it('returns needs_verification true on register when bypass is OFF', function () {
    config(['langkahkecil.bypass.email_verification.api' => false]);
    config(['langkahkecil.verification.register_backend' => false]);

    $response = $this->postJson('/api/register', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'needs_verification' => true,
            'message' => 'Akun berhasil dibuat. Silakan verifikasi.',
        ]);
});

/*
|--------------------------------------------------------------------------
| Me Endpoint Tests
|--------------------------------------------------------------------------
*/

it('returns user data on me endpoint when bypass is ON and user unverified', function () {
    config(['langkahkecil.bypass.email_verification.api' => true]);

    $token = $this->user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/me');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user',
            'plans',
        ]);

    $json = $response->json();
    expect(array_key_exists('needs_verification', $json))->toBeFalse();
});

it('returns needs_verification on me endpoint when bypass is OFF and user unverified', function () {
    config(['langkahkecil.bypass.email_verification.api' => false]);

    $token = $this->user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/me');

    $gateway = config('langkahkecil.verification.gateway', 'email');

    $response->assertStatus(200)
        ->assertJson([
            'needs_verification' => true,
            'verification_gateway' => $gateway,
        ]);
});

/*
|--------------------------------------------------------------------------
| Config Toggle Test
|--------------------------------------------------------------------------
*/

it('reads bypass config correctly from langkahkecil config', function () {
    config(['langkahkecil.bypass.email_verification.api' => true]);
    expect(config('langkahkecil.bypass.email_verification.api'))->toBeTrue();

    config(['langkahkecil.bypass.email_verification.api' => false]);
    expect(config('langkahkecil.bypass.email_verification.api'))->toBeFalse();
});