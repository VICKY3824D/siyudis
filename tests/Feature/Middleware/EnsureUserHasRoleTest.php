<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    $this->roleMahasiswa = Role::create(['name' => 'mahasiswa', 'is_system' => true]);
    $this->roleStafAkademik = Role::create(['name' => 'staf_akademik', 'is_system' => true]);
    $this->roleKaprodi = Role::create(['name' => 'kaprodi', 'is_system' => true]);

    // Create users
    $this->mahasiswa = User::create([
        'nomor_induk' => 'MHS-001',
        'nama' => 'Test Mahasiswa',
        'email' => 'mahasiswa@test.com',
        'role_id' => $this->roleMahasiswa->id,
    ]);

    $this->stafAkademik = User::create([
        'nomor_induk' => 'STAFF-001',
        'nama' => 'Test Staf Akademik',
        'email' => 'staff@test.com',
        'role_id' => $this->roleStafAkademik->id,
    ]);

    $this->kaprodi = User::create([
        'nomor_induk' => 'KAPRODI-001',
        'nama' => 'Test Kaprodi',
        'email' => 'kaprodi@test.com',
        'role_id' => $this->roleKaprodi->id,
    ]);

    // Create test route with middleware
    Route::middleware(['auth:sanctum', 'role:staf_akademik'])->get('/test-role-middleware', function () {
        return response()->json(['message' => 'Access granted']);
    });
});

test('middleware allows access when user has correct role', function () {
    Sanctum::actingAs($this->stafAkademik);

    $response = $this->getJson('/test-role-middleware');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Access granted']);
});

test('middleware denies access when user has different role', function () {
    Sanctum::actingAs($this->mahasiswa);

    $response = $this->getJson('/test-role-middleware');

    $response->assertStatus(403)
        ->assertJson(['message' => 'Forbidden']);
});

test('middleware denies access when user is not logged in', function () {
    $response = $this->getJson('/test-role-middleware');

    $response->assertStatus(401);
});

test('middleware denies access when user role relation is not loaded', function () {
    // Create user and manually unset the role relationship to simulate null role
    $userWithoutRole = User::create([
        'nomor_induk' => 'NO-ROLE-001',
        'nama' => 'User Without Role',
        'email' => 'norole@test.com',
        'role_id' => $this->roleMahasiswa->id,
    ]);

    // Unset the loaded relationship
    $userWithoutRole->unsetRelation('role');

    Sanctum::actingAs($userWithoutRole);

    $response = $this->getJson('/test-role-middleware');

    $response->assertStatus(403)
        ->assertJson(['message' => 'Forbidden']);
});
