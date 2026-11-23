<?php

use App\Models\BeritaAcara;
use App\Models\DataBeritaAcaraMahasiswa;
use App\Models\PengajuanYudisium;
use App\Models\ProgramStudi;
use App\Models\RiwayatPerubahan;
use App\Models\RiwayatPerubahanDetail;
use App\Models\Role;
use App\Models\User;
use App\Models\YudisiumEvent;
use App\Services\PencatatRiwayat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('storePenilaian mencatat riwayat dengan detail field yang berubah', function () {
    $role = Role::factory()->create(['name' => 'staf_akademik']);
    $staf = User::factory()->create(['role_id' => $role->id]);

    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);
    $prodi = ProgramStudi::factory()->create();
    $mahasiswa = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
        'nama' => 'John Doe',
    ]);

    $event = YudisiumEvent::factory()->create();
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($staf)
        ->putJson("/api/staf-akademik/pengajuan/{$pengajuan->id}/penilaian", [
            'ipk' => 3.5,
            'persen_nilai_d' => 10.0,
            'sks_ditempuh' => 144,
            'similarity_index' => 15.0,
            'skor_bahasa_inggris' => 450,
            'sertifikat_level' => 'B2',
            'status_judul_pa' => 'disetujui',
            'sertifikat_kompetensi' => 'Laravel',
            'status_bebas_pelanggaran' => true,
        ]);

    $response->assertOk();
    expect(RiwayatPerubahan::count())->toBe(1);

    $riwayat = RiwayatPerubahan::first();
    expect($riwayat->user_id)->toBe($staf->id);
    expect($riwayat->modul)->toBe('pengajuan_yudisium');
    expect($riwayat->jenis)->toBe('perubahan_data');
    expect($riwayat->pengajuan_id)->toBe($pengajuan->id);
    expect($riwayat->detail()->count())->toBe(9);
});

test('storePenilaian hanya mencatat field yang berubah', function () {
    $role = Role::factory()->create(['name' => 'staf_akademik']);
    $staf = User::factory()->create(['role_id' => $role->id]);

    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);
    $prodi = ProgramStudi::factory()->create();
    $mahasiswa = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    $event = YudisiumEvent::factory()->create();
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'checking_akademik',
    ]);

    DataBeritaAcaraMahasiswa::create([
        'pengajuan_id' => $pengajuan->id,
        'ipk' => 3.5,
        'persen_nilai_d' => 10.0,
        'sks_ditempuh' => 144,
        'similarity_index' => 15.0,
        'skor_bahasa_inggris' => 450,
        'sertifikat_level' => 'B2',
        'status_judul_pa' => 'disetujui',
        'sertifikat_kompetensi' => 'Laravel',
        'status_bebas_pelanggaran' => true,
    ]);

    $response = $this->actingAs($staf)
        ->putJson("/api/staf-akademik/pengajuan/{$pengajuan->id}/penilaian", [
            'ipk' => 3.75,
            'persen_nilai_d' => 10.0,
            'sks_ditempuh' => 144,
            'similarity_index' => 15.0,
            'skor_bahasa_inggris' => 450,
            'sertifikat_level' => 'B2',
            'status_judul_pa' => 'disetujui',
            'sertifikat_kompetensi' => 'Laravel',
            'status_bebas_pelanggaran' => true,
        ]);

    $response->assertOk();

    $riwayat = RiwayatPerubahan::first();
    expect($riwayat->detail()->count())->toBe(1);

    $detail = $riwayat->detail()->first();
    expect($detail->field)->toBe('ipk');
    expect($detail->nilai_lama)->toBe('3.50');
    expect($detail->nilai_baru)->toBe('3.75');
});

test('kirimKonfirmasi mencatat riwayat', function () {
    $role = Role::factory()->create(['name' => 'staf_akademik']);
    $staf = User::factory()->create(['role_id' => $role->id]);

    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);
    $prodi = ProgramStudi::factory()->create();
    $mahasiswa = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
        'email' => 'test@example.com',
    ]);

    $event = YudisiumEvent::factory()->create();
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'checking_akademik',
    ]);

    DataBeritaAcaraMahasiswa::factory()->create([
        'pengajuan_id' => $pengajuan->id,
    ]);

    $response = $this->actingAs($staf)
        ->postJson("/api/staf-akademik/pengajuan/{$pengajuan->id}/kirim-konfirmasi");

    $response->assertOk();
    expect(RiwayatPerubahan::count())->toBe(1);

    $riwayat = RiwayatPerubahan::first();
    expect($riwayat->modul)->toBe('pengajuan_yudisium');
    expect($riwayat->jenis)->toBe('perubahan_data');
    expect($riwayat->pengajuan_id)->toBe($pengajuan->id);
});

test('checklist mencatat riwayat dengan jenis pengesahan', function () {
    $role = Role::factory()->create(['name' => 'staf_akademik']);
    $staf = User::factory()->create(['role_id' => $role->id]);

    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);
    $prodi = ProgramStudi::factory()->create();
    $mahasiswa = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    $event = YudisiumEvent::factory()->create();
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'confirmed_by_student',
    ]);

    $response = $this->actingAs($staf)
        ->postJson("/api/staf-akademik/pengajuan/{$pengajuan->id}/checklist");

    $response->assertOk();
    expect(RiwayatPerubahan::count())->toBe(1);

    $riwayat = RiwayatPerubahan::first();
    expect($riwayat->jenis)->toBe('pengesahan');
});

test('konfirmasi mahasiswa salah mencatat riwayat pengajuan revisi', function () {
    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);
    $prodi = ProgramStudi::factory()->create();
    $mahasiswa = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    $event = YudisiumEvent::factory()->create();
    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'waiting_student_confirmation',
    ]);

    $response = $this->actingAs($mahasiswa)
        ->patchJson('/api/pengajuan/me/konfirmasi', [
            'konfirmasi' => 'salah',
            'catatan' => 'IPK saya salah',
        ]);

    $response->assertOk();
    expect(RiwayatPerubahan::count())->toBe(1);

    $riwayat = RiwayatPerubahan::first();
    expect($riwayat->jenis)->toBe('pengajuan_revisi');
    expect($riwayat->catatan)->toBe('IPK saya salah');
});

test('admin dapat melihat daftar riwayat dengan filter', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $admin = User::factory()->create(['role_id' => $superAdminRole->id]);

    $stafRole = Role::factory()->create(['name' => 'staf_akademik']);
    $staf = User::factory()->create(['role_id' => $stafRole->id]);

    RiwayatPerubahan::factory()->create([
        'user_id' => $staf->id,
        'role_name' => 'staf_akademik',
        'modul' => 'pengajuan_yudisium',
        'jenis' => 'perubahan_data',
        'created_at' => now()->subDays(2),
    ]);

    RiwayatPerubahan::factory()->create([
        'user_id' => $staf->id,
        'role_name' => 'staf_akademik',
        'modul' => 'berita_acara',
        'jenis' => 'pengesahan',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/admin/riwayat?modul=pengajuan_yudisium');

    $response->assertOk();
    expect($response->json('data.data'))->toHaveCount(1);
    expect($response->json('data.data.0.modul'))->toBe('pengajuan_yudisium');
});

test('admin dapat melihat ringkasan per jenis', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $admin = User::factory()->create(['role_id' => $superAdminRole->id]);

    $stafRole = Role::factory()->create(['name' => 'staf_akademik']);
    $staf = User::factory()->create(['role_id' => $stafRole->id]);

    RiwayatPerubahan::factory()->count(3)->create([
        'user_id' => $staf->id,
        'jenis' => 'perubahan_data',
    ]);

    RiwayatPerubahan::factory()->count(2)->create([
        'user_id' => $staf->id,
        'jenis' => 'pengesahan',
    ]);

    RiwayatPerubahan::factory()->create([
        'user_id' => $staf->id,
        'jenis' => 'pengajuan_revisi',
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/admin/riwayat/ringkasan');

    $response->assertOk();
    expect($response->json('data.perubahan_data'))->toBe(3);
    expect($response->json('data.pengesahan'))->toBe(2);
    expect($response->json('data.pengajuan_revisi'))->toBe(1);
    expect($response->json('data.dikembalikan'))->toBe(0);
});

test('admin dapat melihat detail riwayat dengan field yang berubah', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $admin = User::factory()->create(['role_id' => $superAdminRole->id]);

    $stafRole = Role::factory()->create(['name' => 'staf_akademik']);
    $staf = User::factory()->create(['role_id' => $stafRole->id]);

    $riwayat = RiwayatPerubahan::factory()->create([
        'user_id' => $staf->id,
        'jenis' => 'perubahan_data',
    ]);

    RiwayatPerubahanDetail::create([
        'riwayat_perubahan_id' => $riwayat->id,
        'field' => 'ipk',
        'nilai_lama' => '3.5',
        'nilai_baru' => '3.75',
    ]);

    $response = $this->actingAs($admin)
        ->getJson("/api/admin/riwayat/{$riwayat->id}");

    $response->assertOk();
    expect($response->json('data.detail'))->toHaveCount(1);
    expect($response->json('data.detail.0.field'))->toBe('ipk');
    expect($response->json('data.detail.0.nilai_lama'))->toBe('3.5');
    expect($response->json('data.detail.0.nilai_baru'))->toBe('3.75');
});

test('non super admin tidak dapat mengakses endpoint riwayat', function () {
    $stafRole = Role::factory()->create(['name' => 'staf_akademik']);
    $staf = User::factory()->create(['role_id' => $stafRole->id]);

    $response = $this->actingAs($staf)
        ->getJson('/api/admin/riwayat');

    $response->assertForbidden();
});

test('tanpa token tidak dapat mengakses endpoint riwayat', function () {
    $response = $this->getJson('/api/admin/riwayat');
    $response->assertUnauthorized();
});
