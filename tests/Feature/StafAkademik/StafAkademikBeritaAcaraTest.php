<?php

use App\Models\BeritaAcara;
use App\Models\Form;
use App\Models\PengajuanYudisium;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use App\Models\YudisiumEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stafAkademikRole = Role::factory()->create(['name' => 'staf_akademik']);
    $this->superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $this->mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);

    $this->stafAkademik = User::factory()->create(['role_id' => $this->stafAkademikRole->id]);
    $this->superAdmin = User::factory()->create(['role_id' => $this->superAdminRole->id]);

    $this->prodi = ProgramStudi::factory()->create();
    $this->form = Form::factory()->create();
    $this->event = YudisiumEvent::factory()->create([
        'form_id' => $this->form->id,
        'periode' => '2026/2027',
    ]);
});

test('staf akademik dapat membuat berita acara', function () {
    $mahasiswa1 = User::factory()->create([
        'role_id' => $this->mahasiswaRole->id,
        'program_studi_id' => $this->prodi->id,
    ]);
    $mahasiswa2 = User::factory()->create([
        'role_id' => $this->mahasiswaRole->id,
        'program_studi_id' => $this->prodi->id,
    ]);

    $pengajuan1 = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa1->id,
        'yudisium_event_id' => $this->event->id,
        'status' => 'final_checked_akademik',
    ]);
    $pengajuan2 = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa2->id,
        'yudisium_event_id' => $this->event->id,
        'status' => 'final_checked_akademik',
    ]);

    $response = $this->actingAs($this->stafAkademik, 'sanctum')
        ->postJson('/api/staf-akademik/berita-acara', [
            'yudisium_event_id' => $this->event->id,
            'program_studi_id' => $this->prodi->id,
            'pengajuan_ids' => [$pengajuan1->id, $pengajuan2->id],
        ]);

    $response->assertStatus(201)
        ->assertJson(['status' => 'success']);

    $this->assertDatabaseHas('berita_acara', [
        'yudisium_event_id' => $this->event->id,
        'program_studi_id' => $this->prodi->id,
        'periode' => '2026/2027',
        'status' => 'diajukan',
        'diajukan_by' => $this->stafAkademik->id,
    ]);

    $this->assertDatabaseHas('berita_acara_pengajuan', [
        'pengajuan_id' => $pengajuan1->id,
    ]);
    $this->assertDatabaseHas('berita_acara_pengajuan', [
        'pengajuan_id' => $pengajuan2->id,
    ]);
});

test('super admin dapat membuat berita acara', function () {
    $mahasiswa = User::factory()->create([
        'role_id' => $this->mahasiswaRole->id,
        'program_studi_id' => $this->prodi->id,
    ]);
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $this->event->id,
        'status' => 'final_checked_akademik',
    ]);

    $response = $this->actingAs($this->superAdmin, 'sanctum')
        ->postJson('/api/staf-akademik/berita-acara', [
            'yudisium_event_id' => $this->event->id,
            'program_studi_id' => $this->prodi->id,
            'pengajuan_ids' => [$pengajuan->id],
        ]);

    $response->assertStatus(201);
});

test('gagal jika pengajuan bukan final_checked_akademik', function () {
    $mahasiswa = User::factory()->create([
        'role_id' => $this->mahasiswaRole->id,
        'program_studi_id' => $this->prodi->id,
    ]);
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $this->event->id,
        'status' => 'checked_akademik',
    ]);

    $response = $this->actingAs($this->stafAkademik, 'sanctum')
        ->postJson('/api/staf-akademik/berita-acara', [
            'yudisium_event_id' => $this->event->id,
            'program_studi_id' => $this->prodi->id,
            'pengajuan_ids' => [$pengajuan->id],
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'message' => 'Semua pengajuan harus berstatus final_checked_akademik',
        ]);
});

test('gagal jika mahasiswa dari prodi berbeda', function () {
    $prodi2 = ProgramStudi::factory()->create();
    $mahasiswa = User::factory()->create([
        'role_id' => $this->mahasiswaRole->id,
        'program_studi_id' => $prodi2->id,
    ]);
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $this->event->id,
        'status' => 'final_checked_akademik',
    ]);

    $response = $this->actingAs($this->stafAkademik, 'sanctum')
        ->postJson('/api/staf-akademik/berita-acara', [
            'yudisium_event_id' => $this->event->id,
            'program_studi_id' => $this->prodi->id,
            'pengajuan_ids' => [$pengajuan->id],
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'message' => 'Semua mahasiswa harus dari program studi yang sama',
        ]);
});

test('gagal jika pengajuan sudah ada di berita acara lain', function () {
    $mahasiswa = User::factory()->create([
        'role_id' => $this->mahasiswaRole->id,
        'program_studi_id' => $this->prodi->id,
    ]);
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $this->event->id,
        'status' => 'final_checked_akademik',
    ]);

    $beritaAcaraLama = BeritaAcara::factory()->create([
        'yudisium_event_id' => $this->event->id,
        'program_studi_id' => $this->prodi->id,
        'diajukan_by' => $this->stafAkademik->id,
    ]);
    $beritaAcaraLama->pengajuan()->attach($pengajuan->id);

    $response = $this->actingAs($this->stafAkademik, 'sanctum')
        ->postJson('/api/staf-akademik/berita-acara', [
            'yudisium_event_id' => $this->event->id,
            'program_studi_id' => $this->prodi->id,
            'pengajuan_ids' => [$pengajuan->id],
        ]);

    $response->assertStatus(409)
        ->assertJson([
            'status' => 'error',
            'message' => 'Salah satu pengajuan sudah terdaftar di berita acara lain',
        ]);
});

test('staf akademik dapat update berita acara', function () {
    $beritaAcara = BeritaAcara::factory()->create([
        'yudisium_event_id' => $this->event->id,
        'program_studi_id' => $this->prodi->id,
        'diajukan_by' => $this->stafAkademik->id,
    ]);

    $response = $this->actingAs($this->stafAkademik, 'sanctum')
        ->patchJson("/api/staf-akademik/berita-acara/{$beritaAcara->id}", [
            'nomor_surat' => 'BA/001/2026',
            'tanggal_surat' => '2026-10-10',
            'periode' => 'Genap 2025/2026',
        ]);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success']);

    $beritaAcara->refresh();
    expect($beritaAcara->nomor_surat)->toBe('BA/001/2026');
    expect($beritaAcara->tanggal_surat->format('Y-m-d'))->toBe('2026-10-10');
    expect($beritaAcara->periode)->toBe('Genap 2025/2026');
});

test('unauthenticated user cannot create berita acara', function () {
    $response = $this->postJson('/api/staf-akademik/berita-acara', [
        'yudisium_event_id' => $this->event->id,
        'program_studi_id' => $this->prodi->id,
        'pengajuan_ids' => [1],
    ]);

    $response->assertStatus(401);
});
