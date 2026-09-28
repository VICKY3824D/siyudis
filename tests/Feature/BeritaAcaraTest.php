<?php

use App\Models\Form;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use App\Models\YudisiumEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    $this->roleMahasiswa = Role::create(['name' => 'mahasiswa', 'is_system' => true]);
    $this->roleStafAkademik = Role::create(['name' => 'staf_akademik', 'is_system' => true]);

    // Create program studi
    $this->prodi = ProgramStudi::create(['nama_prodi' => 'Teknik Informatika']);

    // Create users
    $this->mahasiswa = User::create([
        'nomor_induk' => 'MHS-001',
        'nama' => 'Mahasiswa Test',
        'email' => 'mahasiswa@test.com',
        'role_id' => $this->roleMahasiswa->id,
        'program_studi_id' => $this->prodi->id,
    ]);

    $this->stafAkademik = User::create([
        'nomor_induk' => 'STAFF-001',
        'nama' => 'Staf Akademik Test',
        'email' => 'staff@test.com',
        'role_id' => $this->roleStafAkademik->id,
        'program_studi_id' => $this->prodi->id,
    ]);

    // Create form and event
    $this->form = Form::create([
        'nama_form' => 'Form Yudisium',
        'deskripsi' => 'Form untuk yudisium',
    ]);

    $this->event = YudisiumEvent::create([
        'form_id' => $this->form->id,
        'nama_event' => 'Yudisium 2026',
        'periode' => 'Genap 2025/2026',
        'tgl_buka' => now()->subDays(5),
        'tgl_tutup' => now()->addDays(10),
        'tgl_yudisium' => now()->addDays(20),
        'is_active' => true,
    ]);
});

test('authenticated user can update berita acara data', function () {
    Sanctum::actingAs($this->stafAkademik);

    $payload = [
        'nomor_surat' => 'BA/001/2026',
        'tanggal_surat' => '2026-09-30',
        'periode' => 'Genap 2025/2026',
    ];

    $response = $this->patchJson('/api/yudisium-events/'.$this->event->id.'/berita-acara', $payload);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success'])
        ->assertJsonFragment(['nomor_surat' => 'BA/001/2026']);

    $this->assertDatabaseHas('yudisium_events', [
        'id' => $this->event->id,
        'nomor_surat' => 'BA/001/2026',
        'tanggal_surat' => '2026-09-30',
        'periode' => 'Genap 2025/2026',
    ]);
});

test('can update partial berita acara data', function () {
    Sanctum::actingAs($this->stafAkademik);

    $payload = [
        'nomor_surat' => 'BA/002/2026',
    ];

    $response = $this->patchJson('/api/yudisium-events/'.$this->event->id.'/berita-acara', $payload);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success']);

    $this->assertDatabaseHas('yudisium_events', [
        'id' => $this->event->id,
        'nomor_surat' => 'BA/002/2026',
    ]);
});

test('unauthenticated user cannot update berita acara', function () {
    $payload = [
        'nomor_surat' => 'BA/001/2026',
    ];

    $response = $this->patchJson('/api/yudisium-events/'.$this->event->id.'/berita-acara', $payload);

    $response->assertStatus(401);
});
