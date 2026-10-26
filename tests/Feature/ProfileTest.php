<?php

use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->roleMahasiswa = Role::create(['name' => 'mahasiswa', 'is_system' => true]);
    $this->roleAdmin = Role::create(['name' => 'super_admin', 'is_system' => true]);

    $this->prodi1 = ProgramStudi::create(['nama_prodi' => 'Teknik Informatika']);
    $this->prodi2 = ProgramStudi::create(['nama_prodi' => 'Teknologi Rekayasa Perangkat Lunak']);

    $this->user = User::create([
        'nomor_induk' => '21/123456/TK/00001',
        'nama' => 'Budi Santoso',
        'email' => 'budi@mail.ugm.ac.id',
        'role_id' => $this->roleMahasiswa->id,
        'program_studi_id' => $this->prodi1->id,
    ]);

    $this->otherUser = User::create([
        'nomor_induk' => '21/999999/TK/99999',
        'nama' => 'Other User',
        'email' => 'other@mail.ugm.ac.id',
        'role_id' => $this->roleMahasiswa->id,
        'program_studi_id' => $this->prodi1->id,
    ]);
});

test('unauthenticated user cannot view or update profile', function () {
    $this->getJson('/api/profile')->assertUnauthorized();
    $this->putJson('/api/profile', ['nama' => 'Test'])->assertUnauthorized();
    $this->patchJson('/api/me', ['nama' => 'Test'])->assertUnauthorized();
});

test('authenticated user can view profile via /api/profile and /api/me', function () {
    Sanctum::actingAs($this->user);

    $responseProfile = $this->getJson('/api/profile');
    $responseProfile->assertOk()
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $this->user->id,
                'nama' => 'Budi Santoso',
                'email' => 'budi@mail.ugm.ac.id',
                'nomor_induk' => '21/123456/TK/00001',
                'program_studi' => [
                    'id' => $this->prodi1->id,
                    'nama_prodi' => 'Teknik Informatika',
                ],
                'role' => [
                    'id' => $this->roleMahasiswa->id,
                    'name' => 'mahasiswa',
                ],
            ],
        ]);

    $responseMe = $this->getJson('/api/me');
    $responseMe->assertOk()
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $this->user->id,
            ],
        ]);
});

test('user can update nama, nomor_induk, and program_studi_id via PUT /api/profile', function () {
    Sanctum::actingAs($this->user);

    $payload = [
        'nama' => 'Budi Santoso Updated',
        'nomor_induk' => '21/654321/TK/00002',
        'program_studi_id' => $this->prodi2->id,
    ];

    $response = $this->putJson('/api/profile', $payload);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'nama' => 'Budi Santoso Updated',
                'nomor_induk' => '21/654321/TK/00002',
                'program_studi_id' => $this->prodi2->id,
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'nama' => 'Budi Santoso Updated',
        'nomor_induk' => '21/654321/TK/00002',
        'program_studi_id' => $this->prodi2->id,
    ]);
});

test('user can update profile via PATCH /api/me', function () {
    Sanctum::actingAs($this->user);

    $response = $this->patchJson('/api/me', [
        'nama' => 'Nama Baru via Me',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'nama' => 'Nama Baru via Me',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'nama' => 'Nama Baru via Me',
    ]);
});

test('user cannot use already taken nomor_induk', function () {
    Sanctum::actingAs($this->user);

    $response = $this->putJson('/api/profile', [
        'nomor_induk' => $this->otherUser->nomor_induk,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nomor_induk']);
});

test('user can keep own nomor_induk when updating other fields', function () {
    Sanctum::actingAs($this->user);

    $response = $this->putJson('/api/profile', [
        'nama' => 'Budi Only Changed Name',
        'nomor_induk' => $this->user->nomor_induk,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'nama' => 'Budi Only Changed Name',
        'nomor_induk' => $this->user->nomor_induk,
    ]);
});

test('user cannot assign non-existent program_studi_id', function () {
    Sanctum::actingAs($this->user);

    $response = $this->putJson('/api/profile', [
        'program_studi_id' => 99999,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['program_studi_id']);
});

test('user can update avatar with URL string', function () {
    Sanctum::actingAs($this->user);

    $avatarUrl = 'https://lh3.googleusercontent.com/a/custom-avatar-url';

    $response = $this->putJson('/api/profile', [
        'avatar' => $avatarUrl,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'data' => [
                'avatar' => $avatarUrl,
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'avatar' => $avatarUrl,
    ]);
});

test('user can upload avatar image file', function () {
    Storage::fake('public');
    Sanctum::actingAs($this->user);

    $file = UploadedFile::fake()->image('profile.jpg', 200, 200);

    $response = $this->postJson('/api/profile', [
        '_method' => 'PUT',
        'avatar' => $file,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
        ]);

    $this->user->refresh();
    expect($this->user->avatar)->toContain('/storage/avatars/');
    Storage::disk('public')->assertExists(str_replace(asset('storage/'), '', $this->user->avatar));
});

test('role_id cannot be modified via profile update endpoint', function () {
    Sanctum::actingAs($this->user);

    $response = $this->putJson('/api/profile', [
        'nama' => 'Hacker Trying Role Escalation',
        'role_id' => $this->roleAdmin->id,
    ]);

    $response->assertOk();
    $this->user->refresh();
    expect($this->user->role_id)->toBe($this->roleMahasiswa->id);
});
