<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Modules\IdentityAccess\Database\Seeders\ConsentTypeSeeder;
use Modules\IdentityAccess\Database\Seeders\PermissionSeeder;
use Modules\IdentityAccess\Database\Seeders\RoleSeeder;
use Modules\IdentityAccess\Database\Seeders\StatusSeeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    Mail::fake();

    (new StatusSeeder())->run();
    (new RoleSeeder())->run();
    (new PermissionSeeder())->run();
    (new ConsentTypeSeeder())->run();

    Http::fake([
        config('services.turnstile.verify_url') => Http::response(['success' => true], 200),
        'https://challenges.cloudflare.com/*'   => Http::response(['success' => true], 200),
    ]);
});

// ─── Forgot password ────────────────────────────────────────────────────────

test('forgot-password odošle reset email pre existujúceho používateľa', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->postJson('/api/auth/forgot-password', [
        'email'                 => $user->email,
        'cf_turnstile_response' => 'test-token',
    ])->assertOk();
});

test('forgot-password vráti 404 pre neexistujúci email', function () {
    $this->postJson('/api/auth/forgot-password', [
        'email'                 => 'nikto@example.com',
        'cf_turnstile_response' => 'test-token',
    ])->assertNotFound();
});

test('forgot-password bez emailu vráti 422', function () {
    $this->postJson('/api/auth/forgot-password', [
        'cf_turnstile_response' => 'test-token',
    ])->assertUnprocessable();
});

// ─── Reset password ─────────────────────────────────────────────────────────

test('reset-password s platným tokenom zmení heslo', function () {
    $user  = User::factory()->create(['email_verified_at' => now()]);
    $token = Password::createToken($user);

    $this->postJson('/api/auth/reset-password', [
        'token'                 => $token,
        'email'                 => $user->email,
        'password'              => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertOk();
});

test('reset-password s neplatným tokenom vráti 422', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->postJson('/api/auth/reset-password', [
        'token'                 => 'neplatny-token',
        'email'                 => $user->email,
        'password'              => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertUnprocessable();
});

test('reset-password so slabým heslom vráti 422', function () {
    $user  = User::factory()->create(['email_verified_at' => now()]);
    $token = Password::createToken($user);

    $this->postJson('/api/auth/reset-password', [
        'token'                 => $token,
        'email'                 => $user->email,
        'password'              => 'slabe',
        'password_confirmation' => 'slabe',
    ])->assertUnprocessable();
});

test('reset-password bez potvrdenia hesla vráti 422', function () {
    $user  = User::factory()->create(['email_verified_at' => now()]);
    $token = Password::createToken($user);

    $this->postJson('/api/auth/reset-password', [
        'token'    => $token,
        'email'    => $user->email,
        'password' => 'NewPassword1!',
        // chýba password_confirmation
    ])->assertUnprocessable();
});
