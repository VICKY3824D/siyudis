<?php

use App\Models\DataBeritaAcaraMahasiswa;
use App\Models\Form;
use App\Models\PengajuanYudisium;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\TandaTangan;
use App\Models\User;
use App\Models\YudisiumEvent;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
});

test('preview mengganti semua placeholder dengan benar', function () {
    // Setup roles
    $stafAkademikRole = Role::factory()->create(['name' => 'staf_akademik']);
    $kaprodiRole = Role::factory()->create(['name' => 'kaprodi']);
    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);

    // Setup users
    $stafAkademik = User::factory()->create(['role_id' => $stafAkademikRole->id]);
    $prodi = ProgramStudi::factory()->create(['nama_prodi' => 'Teknik Informatika']);
    $kaprodi = User::factory()->create([
        'role_id' => $kaprodiRole->id,
        'program_studi_id' => $prodi->id,
        'nama' => 'Dr. Kaprodi',
    ]);
    $prodi->update(['kaprodi_id' => $kaprodi->id]);

    $mahasiswa = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    // Setup yudisium event
    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create([
        'form_id' => $form->id,
        'periode' => '2026/2027',
        'nomor_surat' => '001/BA/2026',
        'tanggal_surat' => '2026-10-05',
    ]);

    // Setup pengajuan
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'final_checked_akademik',
    ]);

    DataBeritaAcaraMahasiswa::factory()->create([
        'pengajuan_id' => $pengajuan->id,
    ]);

    // Test preview
    $response = actingAs($stafAkademik, 'sanctum')
        ->getJson("/api/berita-acara/preview?program_studi_id={$prodi->id}&yudisium_event_id={$event->id}")
        ->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => ['html', 'jumlah_mahasiswa'],
        ]);

    $html = $response->json('data.html');

    // Verifikasi semua placeholder diganti
    expect($html)->toContain('Teknik Informatika');
    expect($html)->toContain('2026/2027');
    expect($html)->toContain('001/BA/2026');
    expect($html)->toContain('2026-10-05');
    expect($html)->not->toContain('{{prodi}}');
    expect($html)->not->toContain('{{periode}}');
});

test('tanda tangan hanya tampil kalau semua mahasiswa di-approve', function () {
    $kaprodiRole = Role::factory()->create(['name' => 'kaprodi']);
    $manitRole = Role::factory()->create(['name' => 'manit']);
    $kadepRole = Role::factory()->create(['name' => 'kadep']);
    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);

    $prodi = ProgramStudi::factory()->create();
    $kaprodi = User::factory()->create(['role_id' => $kaprodiRole->id, 'program_studi_id' => $prodi->id]);
    $manit = User::factory()->create(['role_id' => $manitRole->id]);
    $kadep = User::factory()->create(['role_id' => $kadepRole->id]);
    $prodi->update(['kaprodi_id' => $kaprodi->id]);

    // Create fake signatures
    TandaTangan::factory()->create(['user_id' => $kaprodi->id, 'file_path' => 'tanda-tangan/kaprodi.png']);
    TandaTangan::factory()->create(['user_id' => $manit->id, 'file_path' => 'tanda-tangan/manit.png']);
    TandaTangan::factory()->create(['user_id' => $kadep->id, 'file_path' => 'tanda-tangan/kadep.png']);

    // Create signature files
    Storage::disk('public')->put('tanda-tangan/kaprodi.png', 'fake-image-data');
    Storage::disk('public')->put('tanda-tangan/manit.png', 'fake-image-data');
    Storage::disk('public')->put('tanda-tangan/kadep.png', 'fake-image-data');

    $mahasiswa = User::factory()->create(['role_id' => $mahasiswaRole->id, 'program_studi_id' => $prodi->id]);

    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    // Pengajuan belum di-approve semua
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'final_checked_akademik',
        'approved_kaprodi_by' => $kaprodi->id,
        'approved_kaprodi_at' => now(),
        'approved_manit_by' => null,
        'approved_manit_at' => null,
        'approved_kadep_by' => null,
        'approved_kadep_at' => null,
    ]);

    DataBeritaAcaraMahasiswa::factory()->create(['pengajuan_id' => $pengajuan->id]);

    // Test preview - tanda tangan tidak tampil kecuali kaprodi
    $response = actingAs($kaprodi, 'sanctum')
        ->getJson("/api/berita-acara/preview?program_studi_id={$prodi->id}&yudisium_event_id={$event->id}");

    $html = $response->json('data.html');
    expect($html)->toContain('<img src="data:'); // Kaprodi signature should appear
    expect(substr_count($html, '<img src="data:'))->toBe(1); // Only 1 signature
});

test('export returns PDF with correct content type', function () {
    $manitRole = Role::factory()->create(['name' => 'manit']);
    $kadepRole = Role::factory()->create(['name' => 'kadep']);
    $kaprodiRole = Role::factory()->create(['name' => 'kaprodi']);
    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);

    $prodi = ProgramStudi::factory()->create();
    $kaprodi = User::factory()->create(['role_id' => $kaprodiRole->id, 'program_studi_id' => $prodi->id]);
    $manit = User::factory()->create(['role_id' => $manitRole->id]);
    $kadep = User::factory()->create(['role_id' => $kadepRole->id]);
    $prodi->update(['kaprodi_id' => $kaprodi->id]);

    $mahasiswa = User::factory()->create(['role_id' => $mahasiswaRole->id, 'program_studi_id' => $prodi->id]);

    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    // Pengajuan sudah di-approve semua
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'completed',
        'approved_kaprodi_by' => $kaprodi->id,
        'approved_kaprodi_at' => now(),
        'approved_manit_by' => $manit->id,
        'approved_manit_at' => now(),
        'approved_kadep_by' => $kadep->id,
        'approved_kadep_at' => now(),
    ]);

    DataBeritaAcaraMahasiswa::factory()->create(['pengajuan_id' => $pengajuan->id]);

    // Test export
    actingAs($manit, 'sanctum')
        ->getJson("/api/berita-acara/export?program_studi_id={$prodi->id}&yudisium_event_id={$event->id}")
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/pdf');
});

test('export returns 409 jika belum semua approved', function () {
    $stafAkademikRole = Role::factory()->create(['name' => 'staf_akademik']);
    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);

    $stafAkademik = User::factory()->create(['role_id' => $stafAkademikRole->id]);
    $prodi = ProgramStudi::factory()->create();
    $mahasiswa = User::factory()->create(['role_id' => $mahasiswaRole->id, 'program_studi_id' => $prodi->id]);

    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    // Pengajuan belum di-approve Manit dan Kadep
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'final_checked_akademik',
        'approved_kaprodi_by' => null,
        'approved_kaprodi_at' => null,
        'approved_manit_by' => null,
        'approved_manit_at' => null,
        'approved_kadep_by' => null,
        'approved_kadep_at' => null,
    ]);

    DataBeritaAcaraMahasiswa::factory()->create(['pengajuan_id' => $pengajuan->id]);

    actingAs($stafAkademik, 'sanctum')
        ->getJson("/api/berita-acara/export?program_studi_id={$prodi->id}&yudisium_event_id={$event->id}")
        ->assertStatus(409)
        ->assertJson([
            'status' => 'error',
            'message' => 'Belum semua mahasiswa disetujui oleh Manager IT dan Kepala Departemen',
        ]);
});

test('kaprodi prodi lain tidak dapat mengakses berita acara', function () {
    $kaprodiRole = Role::factory()->create(['name' => 'kaprodi']);
    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);

    $prodi1 = ProgramStudi::factory()->create();
    $prodi2 = ProgramStudi::factory()->create();
    $kaprodi1 = User::factory()->create(['role_id' => $kaprodiRole->id, 'program_studi_id' => $prodi1->id]);
    $mahasiswa2 = User::factory()->create(['role_id' => $mahasiswaRole->id, 'program_studi_id' => $prodi2->id]);

    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa2->id,
        'yudisium_event_id' => $event->id,
        'status' => 'final_checked_akademik',
    ]);

    DataBeritaAcaraMahasiswa::factory()->create(['pengajuan_id' => $pengajuan->id]);

    // Kaprodi prodi 1 mencoba akses berita acara prodi 2
    actingAs($kaprodi1, 'sanctum')
        ->getJson("/api/berita-acara/preview?program_studi_id={$prodi2->id}&yudisium_event_id={$event->id}")
        ->assertStatus(403);
});

test('nilai HTML jahat ter-escape dengan benar', function () {
    $stafAkademikRole = Role::factory()->create(['name' => 'staf_akademik']);
    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);

    $stafAkademik = User::factory()->create(['role_id' => $stafAkademikRole->id]);
    $prodi = ProgramStudi::factory()->create(['nama_prodi' => '<script>alert("xss")</script>']);
    $mahasiswa = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
        'nama' => '<img src=x onerror=alert(1)>',
    ]);

    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'final_checked_akademik',
    ]);

    DataBeritaAcaraMahasiswa::factory()->create([
        'pengajuan_id' => $pengajuan->id,
        'status_judul_pa' => '<b>malicious</b>',
    ]);

    $response = actingAs($stafAkademik, 'sanctum')
        ->getJson("/api/berita-acara/preview?program_studi_id={$prodi->id}&yudisium_event_id={$event->id}");

    $html = $response->json('data.html');

    // Verifikasi HTML ter-escape
    expect($html)->toContain('&lt;script&gt;');
    expect($html)->toContain('&lt;img src=x');
    expect($html)->toContain('&lt;b&gt;malicious&lt;/b&gt;');
    expect($html)->not->toContain('<script>alert("xss")</script>');
    expect($html)->not->toContain('<img src=x onerror=alert(1)>');
});
