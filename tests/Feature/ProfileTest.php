<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Modules\IdentityAccess\Database\Seeders\PermissionSeeder;
use Modules\IdentityAccess\Database\Seeders\RoleSeeder;
use Modules\IdentityAccess\Database\Seeders\StatusSeeder;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    Mail::fake();
    (new StatusSeeder())->run();
    (new RoleSeeder())->run();
    (new PermissionSeeder())->run();
});

function profileUser(): User
{
    $user = User::factory()->create([
        'name'              => 'Ján',
        'surname'           => 'Novák',
        'email_verified_at' => now(),
    ]);
    $user->roles()->sync([Role::where('name', 'student')->firstOrFail()->id]);
    return $user;
}

function profileAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->sync([Role::where('name', 'nti_admin')->firstOrFail()->id]);
    return $user;
}

// ─── GET /api/profile ───────────────────────────────────────────────────────

test('prihlásený používateľ vidí vlastný profil', function () {
    $user = profileUser();
    Sanctum::actingAs($user, ['*']);

    // show() vracia response()->json($user) — bez data wrappera
    $this->getJson('/api/profile')
         ->assertOk()
         ->assertJsonPath('email', $user->email);
});

test('neprihlásený nemôže pristúpiť k profilu', function () {
    $this->getJson('/api/profile')->assertUnauthorized();
});

test('neoverený email nemôže pristúpiť k profilu', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/profile')->assertForbidden();
});

// ─── PATCH /api/profile ─────────────────────────────────────────────────────

test('používateľ môže zmeniť vlastné meno a priezvisko', function () {
    $user = profileUser();
    Sanctum::actingAs($user, ['*']);

    $role = Role::where('name', 'student')->firstOrFail();

    $this->patchJson('/api/profile', [
        'name'    => 'Peter',
        'surname' => 'Horváth',
        'email'   => $user->email,
        'roles'   => [$role->id],
    ])->assertOk();

    expect($user->fresh()->name)->toBe('Peter');
    expect($user->fresh()->surname)->toBe('Horváth');
});

test('používateľ nemôže nastaviť duplicitný email', function () {
    $user  = profileUser();
    $other = User::factory()->create(['email_verified_at' => now()]);
    Sanctum::actingAs($user, ['*']);

    $role = Role::where('name', 'student')->firstOrFail();

    $this->patchJson('/api/profile', [
        'name'    => 'Peter',
        'email'   => $other->email,
        'roles'   => [$role->id],
    ])->assertUnprocessable();
});

test('používateľ nemôže upraviť cudzí profil', function () {
    $user  = profileUser();
    $other = profileUser();
    Sanctum::actingAs($user, ['*']);

    $role = Role::where('name', 'student')->firstOrFail();

    $this->patchJson("/api/users/{$other->id}", [
        'name'  => 'Hacker',
        'email' => $other->email,
        'roles' => [$role->id],
    ])->assertForbidden();
});

test('admin môže upraviť cudzí profil', function () {
    $admin = profileAdmin();
    $user  = profileUser();
    Sanctum::actingAs($admin, ['*']);

    $role = Role::where('name', 'student')->firstOrFail();

    $this->withoutExceptionHandling();

    $this->patchJson("/api/users/{$user->id}", [
        'name'      => 'Upravené',
        'surname'   => $user->surname,
        'email'     => $user->email,
        'roles'     => [$role->id],
        'status_id' => $user->status_id,
    ])->assertOk();
    expect($user->fresh()->name)->toBe('Upravené');
});
