<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Modules\Applications\Database\Seeders\StatusOfApplicationSeeder;
use Modules\Content\Database\Seeders\LanguageSeeder;
use Modules\Content\Models\Language;
use Modules\IdentityAccess\Database\Seeders\PermissionSeeder;
use Modules\IdentityAccess\Database\Seeders\RoleSeeder;
use Modules\IdentityAccess\Database\Seeders\StatusSeeder;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Database\Seeders\CallTypeSeeder;
use Modules\Programs\Database\Seeders\StatusOfCallSeeder;
use Modules\Programs\Database\Seeders\TypeOfProgramSeeder;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
use Modules\Programs\Models\Program;
use Modules\Programs\Models\TypeOfProgram;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    Mail::fake();

    (new StatusSeeder())->run();
    (new RoleSeeder())->run();
    (new PermissionSeeder())->run();
    (new StatusOfApplicationSeeder())->run();
    (new TypeOfProgramSeeder())->run();
    (new CallTypeSeeder())->run();
    (new StatusOfCallSeeder())->run();
    (new LanguageSeeder())->run();
});

// ─── Helpers ────────────────────────────────────────────────────────────────

function callAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->sync([Role::where('name', 'nti_admin')->firstOrFail()->id]);
    return $user;
}

function callStudent(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->sync([Role::where('name', 'student')->firstOrFail()->id]);
    return $user;
}

function programBId(): int
{
    // Program A gets id=1 (machineFor logic), Program B gets id=2
    $typeA = TypeOfProgram::where('name', 'Program A')->firstOrFail();
    Program::firstOrCreate(['type_of_program_id' => $typeA->id]);
    $typeB = TypeOfProgram::where('name', 'Program B')->firstOrFail();
    return Program::firstOrCreate(['type_of_program_id' => $typeB->id])->id;
}

function callPayload(array $overrides = []): array
{
    return array_merge([
        'name'                 => 'Testovacia výzva',
        'description'          => 'Popis výzvy',
        'application_start'    => now()->subDay()->toDateString(),
        'application_deadline' => now()->addDays(7)->toDateString(),
        'project_start'        => now()->addDays(10)->toDateString(),
        'project_end'          => now()->addDays(100)->toDateString(),
        'program_id'           => programBId(),
        'language_id'          => Language::where('name', 'sk')->firstOrFail()->id,
    ], $overrides);
}

function existingCall(array $overrides = []): Call
{
    return Call::create(array_merge([
        'name'                 => 'Existujúca výzva',
        'description'          => 'Popis výzvy',
        'application_start'    => now()->subDay(),
        'application_deadline' => now()->addDays(7),
        'project_start'        => now()->addDays(10),
        'project_end'          => now()->addDays(100),
        'program_id'           => programBId(),
        'call_type_id'         => CallType::first()->id,
        'force_closed'         => false,
    ], $overrides));
}

// ─── GET /api/calls (verejný zoznam) ────────────────────────────────────────

test('verejný zoznam výziev je dostupný bez autentifikácie', function () {
    // Endpoint je verejný — nevyžaduje token
    $this->getJson('/api/calls')->assertOk();
});

// ─── GET /api/v1/admin/calls ────────────────────────────────────────────────

test('admin vidí zoznam výziev na admin endpointe', function () {
    existingCall();
    $admin = callAdmin();
    Sanctum::actingAs($admin, ['*']);

    $this->getJson('/api/v1/admin/calls')
         ->assertOk()
         ->assertJsonStructure(['data', 'total']);
});

test('student vidí prázdny admin zoznam (policy viewAny=true, ale nevidí cudzie výzvy)', function () {
    // CallPolicy::viewAny vracia true pre všetkých — endpoint je dostupný
    // Student bez vlastných výziev dostane prázdny zoznam
    $student = callStudent();
    Sanctum::actingAs($student, ['*']);

    $response = $this->getJson('/api/v1/admin/calls')->assertOk();
    expect($response->json('total'))->toBe(0);
});

// ─── POST /api/v1/admin/calls ───────────────────────────────────────────────

test('admin môže vytvoriť výzvu', function () {
    $admin = callAdmin();
    Sanctum::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/admin/calls', callPayload());

    $response->assertCreated();
    expect(Call::where('name', 'Testovacia výzva')->exists())->toBeTrue();
});

test('student nemôže vytvoriť výzvu', function () {
    $student = callStudent();
    Sanctum::actingAs($student, ['*']);

    $this->postJson('/api/v1/admin/calls', callPayload())->assertForbidden();
});

test('neprihlásený nemôže vytvoriť výzvu', function () {
    $this->postJson('/api/v1/admin/calls', callPayload())->assertUnauthorized();
});

test('vytvorenie výzvy bez povinných polí vráti 422', function () {
    $admin = callAdmin();
    Sanctum::actingAs($admin, ['*']);

    $this->postJson('/api/v1/admin/calls', [
        'name' => 'Chýbajú dátumy',
    ])->assertUnprocessable();
});

test('deadline nesmie byť pred application_start', function () {
    $admin = callAdmin();
    Sanctum::actingAs($admin, ['*']);

    $this->postJson('/api/v1/admin/calls', callPayload([
        'application_start'    => now()->toDateString(),
        'application_deadline' => now()->subDay()->toDateString(),
    ]))->assertUnprocessable();
});

// ─── PUT /api/v1/admin/calls/{id} ───────────────────────────────────────────

test('admin môže upraviť výzvu', function () {
    $admin = callAdmin();
    $call  = existingCall();
    Sanctum::actingAs($admin, ['*']);

    $this->putJson("/api/v1/admin/calls/{$call->id}", callPayload([
        'name' => 'Upravená výzva',
    ]))->assertOk();

    expect($call->fresh()->name)->toBe('Upravená výzva');
});

test('student nemôže upraviť výzvu', function () {
    $student = callStudent();
    $call    = existingCall();
    Sanctum::actingAs($student, ['*']);

    $this->putJson("/api/v1/admin/calls/{$call->id}", callPayload([
        'name' => 'Pokus',
    ]))->assertForbidden();
});

// ─── DELETE /api/v1/admin/calls/{id} ────────────────────────────────────────

test('admin môže zmazať výzvu', function () {
    $admin = callAdmin();
    $call  = existingCall();
    Sanctum::actingAs($admin, ['*']);

    $this->deleteJson("/api/v1/admin/calls/{$call->id}")->assertOk();

    expect(Call::find($call->id))->toBeNull();
});

test('student nemôže zmazať výzvu', function () {
    $student = callStudent();
    $call    = existingCall();
    Sanctum::actingAs($student, ['*']);

    $this->deleteJson("/api/v1/admin/calls/{$call->id}")->assertForbidden();
});
