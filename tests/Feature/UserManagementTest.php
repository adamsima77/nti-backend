<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Modules\IdentityAccess\Database\Seeders\PermissionSeeder;
use Modules\IdentityAccess\Database\Seeders\RoleSeeder;
use Modules\IdentityAccess\Database\Seeders\StatusSeeder;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    (new StatusSeeder())->run();
    (new RoleSeeder())->run();
    (new PermissionSeeder())->run();
});

function umAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->sync([Role::where('name', 'nti_admin')->firstOrFail()->id]);
    return $user;
}

function umStudent(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->sync([Role::where('name', 'student')->firstOrFail()->id]);
    return $user;
}

// ─── GET /api/users ─────────────────────────────────────────────────────────

test('admin vidí zoznam používateľov', function () {
    umStudent();
    umStudent();

    $admin = umAdmin();
    Sanctum::actingAs($admin, ['*']);

    $this->getJson('/api/users')
         ->assertOk()
         ->assertJsonStructure(['data']);
});

test('student nemá prístup k zoznamu používateľov', function () {
    $student = umStudent();
    Sanctum::actingAs($student, ['*']);

    $this->getJson('/api/users')->assertForbidden();
});

test('admin môže filtrovať používateľov podľa roly', function () {
    umStudent();
    umAdmin();

    $admin = umAdmin();
    Sanctum::actingAs($admin, ['*']);

    $response = $this->getJson('/api/users?role=student')->assertOk();
    $roles    = collect($response->json('data'))
        ->flatMap(fn ($u) => collect($u['roles'])->pluck('name'))
        ->unique()
        ->values();

    expect($roles->all())->toBe(['student']);
});

// ─── GET /api/users/{id} ────────────────────────────────────────────────────

test('admin vidí detail ľubovoľného používateľa', function () {
    $admin   = umAdmin();
    $student = umStudent();
    Sanctum::actingAs($admin, ['*']);

    // show() vracia response()->json($user) — bez data wrappera
    $this->getJson("/api/users/{$student->id}")
         ->assertOk()
         ->assertJsonPath('email', $student->email);
});

test('student vidí len vlastný profil cez /users/{id}', function () {
    $student = umStudent();
    $other   = umStudent();
    Sanctum::actingAs($student, ['*']);

    $this->getJson("/api/users/{$student->id}")->assertOk();
    $this->getJson("/api/users/{$other->id}")->assertForbidden();
});

// ─── DELETE /api/users/{id} ─────────────────────────────────────────────────

test('admin môže zmazať používateľa', function () {
    $admin   = umAdmin();
    $student = umStudent();
    Sanctum::actingAs($admin, ['*']);

    $this->deleteJson("/api/users/{$student->id}")->assertOk();

    expect(User::find($student->id))->toBeNull();
});

test('student nemôže zmazať iného používateľa', function () {
    $studentA = umStudent();
    $studentB = umStudent();
    Sanctum::actingAs($studentA, ['*']);

    $this->deleteJson("/api/users/{$studentB->id}")->assertForbidden();
});

// ─── GET /api/calls/{id} (verejný detail výzvy) ─────────────────────────────

test('GET /api/calls/{id} vráti detail výzvy bez autentifikácie', function () {
    (new \Modules\Programs\Database\Seeders\TypeOfProgramSeeder())->run();
    (new \Modules\Programs\Database\Seeders\CallTypeSeeder())->run();

    $type    = \Modules\Programs\Models\TypeOfProgram::first();
    $program = \Modules\Programs\Models\Program::firstOrCreate(['type_of_program_id' => $type->id]);

    $call = \Modules\Programs\Models\Call::create([
        'name'                 => 'Verejná výzva',
        'description'          => 'Popis',
        'application_start'    => now()->subDay(),
        'application_deadline' => now()->addDays(7),
        'project_start'        => now()->addDays(10),
        'project_end'          => now()->addDays(100),
        'program_id'           => $program->id,
        'call_type_id'         => \Modules\Programs\Models\CallType::first()->id,
        'force_closed'         => false,
    ]);

    // show() vracia response()->json(new CallResource($call)) — bez data wrappera
    $this->getJson("/api/calls/{$call->id}")
         ->assertOk()
         ->assertJsonPath('id', $call->id);
});

test('GET /api/calls/{id} s neexistujúcim ID vráti 404', function () {
    $this->getJson('/api/calls/99999')->assertNotFound();
});

// ─── GET /api/v1/admin/calls/{id} ───────────────────────────────────────────

test('admin vidí rozšírený detail výzvy', function () {
    (new \Modules\Programs\Database\Seeders\TypeOfProgramSeeder())->run();
    (new \Modules\Programs\Database\Seeders\CallTypeSeeder())->run();
    (new \Modules\Programs\Database\Seeders\StatusOfCallSeeder())->run();
    (new \Modules\Content\Database\Seeders\LanguageSeeder())->run();

    $admin = umAdmin();
    Sanctum::actingAs($admin, ['*']);

    $type    = \Modules\Programs\Models\TypeOfProgram::first();
    $program = \Modules\Programs\Models\Program::firstOrCreate(['type_of_program_id' => $type->id]);

    $call = \Modules\Programs\Models\Call::create([
        'name'                 => 'Admin výzva',
        'description'          => 'Popis',
        'application_start'    => now()->subDay(),
        'application_deadline' => now()->addDays(7),
        'project_start'        => now()->addDays(10),
        'project_end'          => now()->addDays(100),
        'program_id'           => $program->id,
        'call_type_id'         => \Modules\Programs\Models\CallType::first()->id,
        'force_closed'         => false,
    ]);

    // adminShow() vracia response()->json(new CallResource($call)) — bez data wrappera
    $this->getJson("/api/v1/admin/calls/{$call->id}")
         ->assertOk()
         ->assertJsonPath('id', $call->id);
});
