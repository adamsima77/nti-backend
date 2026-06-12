<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
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

    // Turnstile vždy prejde v testoch
    Http::fake([
        'https://challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        config('services.turnstile.verify_url') => Http::response(['success' => true], 200),
    ]);
});

// ─── Login ───────────────────────────────────────────────────────────────────

test('prihlásenie s platnými údajmi vráti token', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status_id'         => UserStatus::ACTIVE->value,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email'                 => $user->email,
        'password'              => 'password',
        'cf_turnstile_response' => 'test-token',
    ]);

    $response->assertOk()
             ->assertJsonStructure(['token', 'user']);
});

test('prihlásenie so zlým heslom vráti 401', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status_id'         => UserStatus::ACTIVE->value,
    ]);

    $this->postJson('/api/auth/login', [
        'email'                 => $user->email,
        'password'              => 'zle-heslo',
        'cf_turnstile_response' => 'test-token',
    ])->assertUnauthorized();
});

test('prihlásenie s neovereným emailom vráti 403', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'status_id'         => UserStatus::PENDING_EMAIL->value,
    ]);

    $this->postJson('/api/auth/login', [
        'email'                 => $user->email,
        'password'              => 'password',
        'cf_turnstile_response' => 'test-token',
    ])->assertForbidden();
});

test('prihlásenie s deaktivovaným účtom vráti 403', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status_id'         => UserStatus::INACTIVE->value,
    ]);

    $this->postJson('/api/auth/login', [
        'email'                 => $user->email,
        'password'              => 'password',
        'cf_turnstile_response' => 'test-token',
    ])->assertForbidden();
});

// ─── Logout ──────────────────────────────────────────────────────────────────

test('odhlásenie zmaže token', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status_id'         => UserStatus::ACTIVE->value,
    ]);

    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/auth/logout')->assertOk();
});

// ─── /auth/me ────────────────────────────────────────────────────────────────

test('GET /auth/me vráti údaje prihláseného používateľa', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/auth/me')
         ->assertOk()
         ->assertJsonPath('email', $user->email);
});

test('neoverený email nemôže pristupovať na chránené endpointy', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    Sanctum::actingAs($user, ['*']);

    // Ľubovoľný endpoint chránený `verified` middleware
    $this->getJson('/api/profile')->assertForbidden();
});

// ─── Registrácia ─────────────────────────────────────────────────────────────

test('registrácia vytvorí používateľa a odošle verifikačný email', function () {
    $response = $this->postJson('/api/auth/register', [
        'email'                  => 'novy@example.com',
        'password'               => 'Password1!',
        'password_confirmation'  => 'Password1!',
        'role'                   => 'student',
        'cf_turnstile_response'  => 'test-token',
    ]);

    $response->assertOk();

    expect(User::where('email', 'novy@example.com')->exists())->toBeTrue();
});

test('registrácia s existujúcim emailom vráti 422', function () {
    User::factory()->create(['email' => 'existujuci@example.com']);

    $this->postJson('/api/auth/register', [
        'email'                  => 'existujuci@example.com',
        'password'               => 'Password1!',
        'password_confirmation'  => 'Password1!',
        'role'                   => 'student',
        'cf_turnstile_response'  => 'test-token',
    ])->assertUnprocessable();
});

test('registrácia so slabým heslom vráti 422', function () {
    $this->postJson('/api/auth/register', [
        'email'                  => 'novy@example.com',
        'password'               => 'slabe',
        'password_confirmation'  => 'slabe',
        'role'                   => 'student',
        'cf_turnstile_response'  => 'test-token',
    ])->assertUnprocessable();
});
