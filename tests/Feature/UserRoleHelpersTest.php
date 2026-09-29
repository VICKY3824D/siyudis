<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create all roles
    $this->roleMahasiswa = Role::create(['name' => 'mahasiswa', 'is_system' => true]);
    $this->roleStafAkademik = Role::create(['name' => 'staf_akademik', 'is_system' => true]);
    $this->roleKaprodi = Role::create(['name' => 'kaprodi', 'is_system' => true]);
    $this->roleManit = Role::create(['name' => 'manit', 'is_system' => true]);
    $this->roleKadep = Role::create(['name' => 'kadep', 'is_system' => true]);
});

test('isMahasiswa returns true when user has mahasiswa role', function () {
    $user = User::create([
        'nomor_induk' => 'MHS-001',
        'nama' => 'Test Mahasiswa',
        'email' => 'mhs@test.com',
        'role_id' => $this->roleMahasiswa->id,
    ]);

    expect($user->isMahasiswa())->toBeTrue();
    expect($user->isStafAkademik())->toBeFalse();
    expect($user->isKaprodi())->toBeFalse();
    expect($user->isManit())->toBeFalse();
    expect($user->isKadep())->toBeFalse();
});

test('isStafAkademik returns true when user has staf_akademik role', function () {
    $user = User::create([
        'nomor_induk' => 'STAFF-001',
        'nama' => 'Test Staf',
        'email' => 'staff@test.com',
        'role_id' => $this->roleStafAkademik->id,
    ]);

    expect($user->isStafAkademik())->toBeTrue();
    expect($user->isMahasiswa())->toBeFalse();
    expect($user->isKaprodi())->toBeFalse();
    expect($user->isManit())->toBeFalse();
    expect($user->isKadep())->toBeFalse();
});

test('isKaprodi returns true when user has kaprodi role', function () {
    $user = User::create([
        'nomor_induk' => 'KAPRODI-001',
        'nama' => 'Test Kaprodi',
        'email' => 'kaprodi@test.com',
        'role_id' => $this->roleKaprodi->id,
    ]);

    expect($user->isKaprodi())->toBeTrue();
    expect($user->isMahasiswa())->toBeFalse();
    expect($user->isStafAkademik())->toBeFalse();
    expect($user->isManit())->toBeFalse();
    expect($user->isKadep())->toBeFalse();
});

test('isManit returns true when user has manit role', function () {
    $user = User::create([
        'nomor_induk' => 'MANIT-001',
        'nama' => 'Test Manit',
        'email' => 'manit@test.com',
        'role_id' => $this->roleManit->id,
    ]);

    expect($user->isManit())->toBeTrue();
    expect($user->isMahasiswa())->toBeFalse();
    expect($user->isStafAkademik())->toBeFalse();
    expect($user->isKaprodi())->toBeFalse();
    expect($user->isKadep())->toBeFalse();
});

test('isKadep returns true when user has kadep role', function () {
    $user = User::create([
        'nomor_induk' => 'KADEP-001',
        'nama' => 'Test Kadep',
        'email' => 'kadep@test.com',
        'role_id' => $this->roleKadep->id,
    ]);

    expect($user->isKadep())->toBeTrue();
    expect($user->isMahasiswa())->toBeFalse();
    expect($user->isStafAkademik())->toBeFalse();
    expect($user->isKaprodi())->toBeFalse();
    expect($user->isManit())->toBeFalse();
});

test('all helper methods return false when user role relation is null', function () {
    // Create user with a role first
    $user = User::create([
        'nomor_induk' => 'NO-ROLE-001',
        'nama' => 'User Without Role',
        'email' => 'norole@test.com',
        'role_id' => $this->roleMahasiswa->id,
    ]);

    // Manually unset the relationship to simulate null role (like when role is not eager loaded)
    $user->setRelation('role', null);

    expect($user->isMahasiswa())->toBeFalse();
    expect($user->isStafAkademik())->toBeFalse();
    expect($user->isKaprodi())->toBeFalse();
    expect($user->isManit())->toBeFalse();
    expect($user->isKadep())->toBeFalse();
});
