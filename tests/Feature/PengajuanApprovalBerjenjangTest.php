<?php

use App\Models\Form;
use App\Models\PengajuanYudisium;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\TandaTangan;
use App\Models\User;
use App\Models\YudisiumEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->roleMahasiswa = Role::create(['name' => 'mahasiswa', 'is_system' => true]);
    $this->roleKaprodi = Role::create(['name' => 'kaprodi', 'is_system' => true]);
    $this->roleManit = Role::create(['name' => 'manit', 'is_system' => true]);
    $this->roleKadep = Role::create(['name' => 'kadep', 'is_system' => true]);

    $this->prodiA = ProgramStudi::create(['nama_prodi' => 'Teknik Informatika']);
    $this->prodiB = ProgramStudi::create(['nama_prodi' => 'Teknik Elektro']);

    $this->kaprodiA = User::create([
        'nomor_induk' => 'KAPRODI-A',
        'nama' => 'Kaprodi TI',
        'email' => 'kaprodi.a@ugm.ac.id',
        'role_id' => $this->roleKaprodi->id,
        'program_studi_id' => $this->prodiA->id,
    ]);
    $this->prodiA->update(['kaprodi_id' => $this->kaprodiA->id]);

    $this->kaprodiB = User::create([
        'nomor_induk' => 'KAPRODI-B',
        'nama' => 'Kaprodi TE',
        'email' => 'kaprodi.b@ugm.ac.id',
        'role_id' => $this->roleKaprodi->id,
        'program_studi_id' => $this->prodiB->id,
    ]);
    $this->prodiB->update(['kaprodi_id' => $this->kaprodiB->id]);

    $this->manit = User::create([
        'nomor_induk' => 'MANIT-001',
        'nama' => 'Manit',
        'email' => 'manit@ugm.ac.id',
        'role_id' => $this->roleManit->id,
    ]);

    $this->kadep = User::create([
        'nomor_induk' => 'KADEP-001',
        'nama' => 'Kadep',
        'email' => 'kadep@ugm.ac.id',
        'role_id' => $this->roleKadep->id,
    ]);

    $this->mahasiswaA = User::create([
        'nomor_induk' => 'MHS-A-001',
        'nama' => 'Mahasiswa Prodi A',
        'email' => 'mhs.a@mail.ugm.ac.id',
        'role_id' => $this->roleMahasiswa->id,
        'program_studi_id' => $this->prodiA->id,
    ]);

    $form = Form::create(['nama_form' => 'Form Yudisium', 'deskripsi' => 'Form yudisium']);

    $event = YudisiumEvent::create([
        'form_id' => $form->id,
        'nama_event' => 'Yudisium Ganjil',
        'periode' => '2026/2027',
        'tgl_buka' => now()->subDay(),
        'tgl_tutup' => now()->addDays(30),
        'is_active' => true,
    ]);

    // Pengajuan sudah lolos checklist staf akademik -> siap di-review Kaprodi
    $this->pengajuan = PengajuanYudisium::create([
        'user_id' => $this->mahasiswaA->id,
        'yudisium_event_id' => $event->id,
        'status' => 'final_checked_akademik',
    ]);

    // Semua approver sudah upload e-signature (prasyarat approve)
    foreach ([$this->kaprodiA, $this->kaprodiB, $this->manit, $this->kadep] as $approver) {
        TandaTangan::create(['user_id' => $approver->id, 'file_path' => "tanda-tangan/{$approver->id}.png"]);
    }
});

// --- Kaprodi ---

test('kaprodi bisa approve mahasiswa dari prodi yang dia pimpin', function () {
    Sanctum::actingAs($this->kaprodiA);

    $response = $this->patchJson("/api/kaprodi/pengajuan/{$this->pengajuan->id}/approve");

    $response->assertOk();

    $this->pengajuan->refresh();
    expect($this->pengajuan->status)->toBe('approved_kaprodi');
    expect($this->pengajuan->approved_kaprodi_by)->toBe($this->kaprodiA->id);
    expect($this->pengajuan->approved_kaprodi_at)->not->toBeNull();
});

test('kaprodi tidak bisa approve mahasiswa dari prodi lain', function () {
    Sanctum::actingAs($this->kaprodiB);

    $response = $this->patchJson("/api/kaprodi/pengajuan/{$this->pengajuan->id}/approve");

    $response->assertStatus(403);

    $this->pengajuan->refresh();
    expect($this->pengajuan->status)->toBe('final_checked_akademik');
});

test('kaprodi tidak bisa approve tanpa e-signature', function () {
    $this->kaprodiA->tandaTangan()->delete();
    Sanctum::actingAs($this->kaprodiA);

    $response = $this->patchJson("/api/kaprodi/pengajuan/{$this->pengajuan->id}/approve");

    $response->assertStatus(422);
});

test('kaprodi reject mengembalikan pengajuan ke staf akademik dengan catatan', function () {
    Sanctum::actingAs($this->kaprodiA);

    $response = $this->patchJson("/api/kaprodi/pengajuan/{$this->pengajuan->id}/reject", [
        'catatan_revisi_internal' => 'Dokumen SKS belum sesuai',
    ]);

    $response->assertOk();

    $this->pengajuan->refresh();
    expect($this->pengajuan->status)->toBe('checking_akademik');
    expect($this->pengajuan->catatan_revisi_internal)->toBe('Dokumen SKS belum sesuai');
});

test('kaprodi reject wajib mengisi catatan_revisi_internal', function () {
    Sanctum::actingAs($this->kaprodiA);

    $response = $this->patchJson("/api/kaprodi/pengajuan/{$this->pengajuan->id}/reject", []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['catatan_revisi_internal']);
});

test('bukan kaprodi tidak bisa akses endpoint kaprodi', function () {
    Sanctum::actingAs($this->mahasiswaA);

    $response = $this->getJson('/api/kaprodi/pengajuan');

    $response->assertStatus(403);
});

// --- Manit & Kadep (paralel) ---

test('manit approve saja belum menyelesaikan pengajuan, masih menunggu kadep', function () {
    $this->pengajuan->update([
        'status' => 'approved_kaprodi',
        'approved_kaprodi_by' => $this->kaprodiA->id,
        'approved_kaprodi_at' => now(),
    ]);

    Sanctum::actingAs($this->manit);

    $response = $this->patchJson("/api/manit/pengajuan/{$this->pengajuan->id}/approve");

    $response->assertOk();

    $this->pengajuan->refresh();
    expect($this->pengajuan->approved_manit_at)->not->toBeNull();
    expect($this->pengajuan->status)->toBe('approved_kaprodi'); // belum completed
});

test('pengajuan selesai (completed) setelah manit DAN kadep approve, urutan bebas', function () {
    $this->pengajuan->update([
        'status' => 'approved_kaprodi',
        'approved_kaprodi_by' => $this->kaprodiA->id,
        'approved_kaprodi_at' => now(),
    ]);

    // Kadep approve duluan
    Sanctum::actingAs($this->kadep);
    $this->patchJson("/api/kadep/pengajuan/{$this->pengajuan->id}/approve")->assertOk();

    $this->pengajuan->refresh();
    expect($this->pengajuan->status)->toBe('approved_kaprodi'); // masih menunggu manit

    // Manit approve belakangan -> baru completed
    Sanctum::actingAs($this->manit);
    $this->patchJson("/api/manit/pengajuan/{$this->pengajuan->id}/approve")->assertOk();

    $this->pengajuan->refresh();
    expect($this->pengajuan->status)->toBe('completed');
    expect($this->pengajuan->approved_manit_at)->not->toBeNull();
    expect($this->pengajuan->approved_kadep_at)->not->toBeNull();
});

test('manit tidak bisa approve dua kali', function () {
    $this->pengajuan->update([
        'status' => 'approved_kaprodi',
        'approved_kaprodi_by' => $this->kaprodiA->id,
        'approved_kaprodi_at' => now(),
        'approved_manit_by' => $this->manit->id,
        'approved_manit_at' => now(),
    ]);

    Sanctum::actingAs($this->manit);

    $response = $this->patchJson("/api/manit/pengajuan/{$this->pengajuan->id}/approve");

    $response->assertStatus(409);
});

test('kadep reject mereset semua approval (kaprodi & manit ikut null lagi)', function () {
    $this->pengajuan->update([
        'status' => 'approved_kaprodi',
        'approved_kaprodi_by' => $this->kaprodiA->id,
        'approved_kaprodi_at' => now(),
        'approved_manit_by' => $this->manit->id,
        'approved_manit_at' => now(),
    ]);

    Sanctum::actingAs($this->kadep);

    $response = $this->patchJson("/api/kadep/pengajuan/{$this->pengajuan->id}/reject", [
        'catatan_revisi_internal' => 'Nilai similarity index perlu dicek ulang',
    ]);

    $response->assertOk();

    $this->pengajuan->refresh();
    expect($this->pengajuan->status)->toBe('checking_akademik');
    expect($this->pengajuan->approved_kaprodi_at)->toBeNull();
    expect($this->pengajuan->approved_manit_at)->toBeNull();
    expect($this->pengajuan->approved_kadep_at)->toBeNull();
});

test('manit tidak bisa approve sebelum kaprodi approve', function () {
    // status masih final_checked_akademik, belum approved_kaprodi
    Sanctum::actingAs($this->manit);

    $response = $this->patchJson("/api/manit/pengajuan/{$this->pengajuan->id}/approve");

    $response->assertStatus(409);
});
