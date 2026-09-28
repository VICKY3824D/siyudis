<?php

use App\Models\DataBeritaAcaraMahasiswa;
use App\Models\Form;
use App\Models\PengajuanYudisium;
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

    // Create pengajuan
    $this->pengajuan = PengajuanYudisium::create([
        'user_id' => $this->mahasiswa->id,
        'yudisium_event_id' => $this->event->id,
        'status' => 'waiting_student_confirmation',
        'submitted_at' => now(),
    ]);
});

test('mahasiswa can view rekap data penilaian', function () {
    Sanctum::actingAs($this->mahasiswa);

    DataBeritaAcaraMahasiswa::create([
        'pengajuan_id' => $this->pengajuan->id,
        'ipk' => 3.75,
        'persen_nilai_d' => 5.50,
        'sks_ditempuh' => 144,
        'similarity_index' => 15.25,
        'skor_bahasa_inggris' => 550,
        'status_judul_pa' => 'disetujui',
        'status_bebas_pelanggaran' => true,
    ]);

    $response = $this->getJson('/api/pengajuan/me/rekap');

    $response->assertStatus(200)
        ->assertJson(['status' => 'success'])
        ->assertJsonFragment(['ipk' => 3.75]);
});

test('returns 404 when rekap data not available', function () {
    Sanctum::actingAs($this->mahasiswa);

    $response = $this->getJson('/api/pengajuan/me/rekap');

    $response->assertStatus(404)
        ->assertJson(['status' => 'error']);
});

test('mahasiswa can confirm data as benar', function () {
    Sanctum::actingAs($this->mahasiswa);

    $response = $this->patchJson('/api/pengajuan/me/konfirmasi', [
        'konfirmasi' => 'benar',
    ]);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success']);

    $this->assertDatabaseHas('pengajuan_yudisium', [
        'id' => $this->pengajuan->id,
        'status' => 'confirmed_by_student',
    ]);
});

test('mahasiswa can mark data as salah with catatan', function () {
    Sanctum::actingAs($this->mahasiswa);

    $response = $this->patchJson('/api/pengajuan/me/konfirmasi', [
        'konfirmasi' => 'salah',
        'catatan' => 'IPK saya seharusnya 3.80, bukan 3.75',
    ]);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success']);

    $this->assertDatabaseHas('pengajuan_yudisium', [
        'id' => $this->pengajuan->id,
        'status' => 'student_revision_requested',
        'catatan_koreksi_mahasiswa' => 'IPK saya seharusnya 3.80, bukan 3.75',
    ]);
});

test('cannot confirm when status is not waiting_student_confirmation', function () {
    Sanctum::actingAs($this->mahasiswa);

    $this->pengajuan->update(['status' => 'submitted']);

    $response = $this->patchJson('/api/pengajuan/me/konfirmasi', [
        'konfirmasi' => 'benar',
    ]);

    $response->assertStatus(409)
        ->assertJson(['status' => 'error']);
});

test('staf akademik cannot access mahasiswa konfirmasi endpoints', function () {
    Sanctum::actingAs($this->stafAkademik);

    $response = $this->getJson('/api/pengajuan/me/rekap');

    $response->assertStatus(403)
        ->assertJson(['message' => 'Forbidden']);
});
