<?php

use App\Jobs\KirimEmailKonfirmasiJob;
use App\Mail\RekapDataMahasiswaMail;
use App\Models\DataBeritaAcaraMahasiswa;
use App\Models\Form;
use App\Models\PengajuanYudisium;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use App\Models\YudisiumEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
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
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);
});

test('staf akademik can view dashboard with filters', function () {
    Sanctum::actingAs($this->stafAkademik);

    $response = $this->getJson('/api/staf-akademik/pengajuan?status=submitted&program_studi_id='.$this->prodi->id);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success'])
        ->assertJsonStructure(['status', 'data' => ['data']]);
});

test('staf akademik can view pengajuan detail', function () {
    Sanctum::actingAs($this->stafAkademik);

    $response = $this->getJson('/api/staf-akademik/pengajuan/'.$this->pengajuan->id);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success'])
        ->assertJsonFragment(['id' => $this->pengajuan->id]);
});

test('staf akademik can input penilaian data', function () {
    Sanctum::actingAs($this->stafAkademik);

    $payload = [
        'ipk' => 3.75,
        'persen_nilai_d' => 5.50,
        'sks_ditempuh' => 144,
        'similarity_index' => 15.25,
        'skor_bahasa_inggris' => 550,
        'sertifikat_level' => 'Intermediate',
        'status_judul_pa' => 'disetujui',
        'sertifikat_kompetensi' => 'Data Science',
        'status_bebas_pelanggaran' => true,
    ];

    $response = $this->putJson('/api/staf-akademik/pengajuan/'.$this->pengajuan->id.'/penilaian', $payload);

    $response->assertStatus(200)
        ->assertJson(['status' => 'success'])
        ->assertJsonFragment(['ipk' => 3.75]);

    $this->assertDatabaseHas('data_berita_acara_mahasiswa', [
        'pengajuan_id' => $this->pengajuan->id,
        'ipk' => 3.75,
    ]);

    // Status berubah dari submitted -> checking_akademik
    $this->assertDatabaseHas('pengajuan_yudisium', [
        'id' => $this->pengajuan->id,
        'status' => 'checking_akademik',
    ]);
});

test('cannot input penilaian when status is invalid', function () {
    Sanctum::actingAs($this->stafAkademik);

    $this->pengajuan->update(['status' => 'confirmed_by_student']);

    $payload = [
        'ipk' => 3.75,
        'persen_nilai_d' => 5.50,
        'sks_ditempuh' => 144,
        'similarity_index' => 15.25,
        'skor_bahasa_inggris' => 550,
        'status_judul_pa' => 'disetujui',
        'status_bebas_pelanggaran' => true,
    ];

    $response = $this->putJson('/api/staf-akademik/pengajuan/'.$this->pengajuan->id.'/penilaian', $payload);

    $response->assertStatus(409)
        ->assertJson(['status' => 'error']);
});

test('mahasiswa cannot access staf akademik endpoints', function () {
    Sanctum::actingAs($this->mahasiswa);

    $response = $this->getJson('/api/staf-akademik/pengajuan');

    $response->assertStatus(403)
        ->assertJson(['message' => 'Forbidden']);
});

test('staf akademik can send email confirmation (async via queue)', function () {
    Queue::fake();

    Sanctum::actingAs($this->stafAkademik);

    // Create data penilaian first
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

    $this->pengajuan->update(['status' => 'checking_akademik']);

    $response = $this->postJson('/api/staf-akademik/pengajuan/'.$this->pengajuan->id.'/kirim-konfirmasi');

    // Assert 202 Accepted (async operation)
    $response->assertStatus(202)
        ->assertJson(['status' => 'success'])
        ->assertJsonFragment(['email_status' => 'pending'])
        ->assertJsonFragment(['email_attempts' => 1]);

    // Assert job was dispatched
    Queue::assertPushed(KirimEmailKonfirmasiJob::class, function ($job) {
        return $job->pengajuan->id === $this->pengajuan->id;
    });

    // Assert email status is pending and attempts incremented
    $this->assertDatabaseHas('pengajuan_yudisium', [
        'id' => $this->pengajuan->id,
        'email_status' => 'pending',
        'email_attempts' => 1,
    ]);
});

test('cannot send confirmation without penilaian data', function () {
    Sanctum::actingAs($this->stafAkademik);

    $this->pengajuan->update(['status' => 'checking_akademik']);

    $response = $this->postJson('/api/staf-akademik/pengajuan/'.$this->pengajuan->id.'/kirim-konfirmasi');

    $response->assertStatus(422)
        ->assertJson(['status' => 'error']);
});

test('cannot send confirmation when status is invalid', function () {
    Sanctum::actingAs($this->stafAkademik);

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

    $this->pengajuan->update(['status' => 'confirmed_by_student']);

    $response = $this->postJson('/api/staf-akademik/pengajuan/'.$this->pengajuan->id.'/kirim-konfirmasi');

    $response->assertStatus(409)
        ->assertJson(['status' => 'error']);
});

test('staf akademik can checklist pengajuan', function () {
    Sanctum::actingAs($this->stafAkademik);

    $this->pengajuan->update(['status' => 'confirmed_by_student']);

    $response = $this->postJson('/api/staf-akademik/pengajuan/'.$this->pengajuan->id.'/checklist');

    $response->assertStatus(200)
        ->assertJson(['status' => 'success']);

    $this->assertDatabaseHas('pengajuan_yudisium', [
        'id' => $this->pengajuan->id,
        'status' => 'final_checked_akademik',
        'checked_akademik_by' => $this->stafAkademik->id,
    ]);
});

test('cannot checklist when status is not confirmed_by_student', function () {
    Sanctum::actingAs($this->stafAkademik);

    $this->pengajuan->update(['status' => 'submitted']);

    $response = $this->postJson('/api/staf-akademik/pengajuan/'.$this->pengajuan->id.'/checklist');

    $response->assertStatus(409)
        ->assertJson(['status' => 'error']);
});

test('cannot send email when status is pending (concurrent send blocked)', function () {
    Sanctum::actingAs($this->stafAkademik);

    // Create data penilaian
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

    // Set email status to pending
    $this->pengajuan->update([
        'status' => 'checking_akademik',
        'email_status' => 'pending',
    ]);

    $response = $this->postJson('/api/staf-akademik/pengajuan/'.$this->pengajuan->id.'/kirim-konfirmasi');

    $response->assertStatus(409)
        ->assertJson([
            'status' => 'error',
            'message' => 'Email sedang dalam proses pengiriman, mohon tunggu.',
        ]);
});

test('job successfully sends email and updates status', function () {
    Mail::fake();

    // Create data penilaian
    $dataBeritaAcara = DataBeritaAcaraMahasiswa::create([
        'pengajuan_id' => $this->pengajuan->id,
        'ipk' => 3.75,
        'persen_nilai_d' => 5.50,
        'sks_ditempuh' => 144,
        'similarity_index' => 15.25,
        'skor_bahasa_inggris' => 550,
        'status_judul_pa' => 'disetujui',
        'status_bebas_pelanggaran' => true,
    ]);

    $this->pengajuan->update(['status' => 'checking_akademik']);

    // Execute job
    $job = new KirimEmailKonfirmasiJob($this->pengajuan);
    $job->handle();

    // Assert email was sent
    Mail::assertSent(RekapDataMahasiswaMail::class, function ($mail) {
        return $mail->hasTo($this->mahasiswa->email);
    });

    // Assert status updated
    $this->assertDatabaseHas('pengajuan_yudisium', [
        'id' => $this->pengajuan->id,
        'email_status' => 'sent',
        'status' => 'waiting_student_confirmation',
    ]);

    // Assert email_sent_at is not null
    $this->pengajuan->refresh();
    expect($this->pengajuan->email_sent_at)->not->toBeNull();
    expect($this->pengajuan->email_error)->toBeNull();
});

test('job failure updates email_status to failed and stores error', function () {
    Mail::shouldReceive('to')
        ->andThrow(new \Exception('SMTP connection failed'));

    // Create data penilaian
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

    $this->pengajuan->update(['status' => 'checking_akademik']);

    $job = new KirimEmailKonfirmasiJob($this->pengajuan);

    try {
        $job->handle();
    } catch (\Exception $e) {
        $job->failed($e);
    }

    // Assert email_status is failed
    $this->assertDatabaseHas('pengajuan_yudisium', [
        'id' => $this->pengajuan->id,
        'email_status' => 'failed',
        'status' => 'checking_akademik', // Status tidak berubah
    ]);

    // Assert error is stored
    $this->pengajuan->refresh();
    expect($this->pengajuan->email_error)->toContain('SMTP connection failed');
});

test('can resend email after failure (attempts incremented)', function () {
    Queue::fake();

    Sanctum::actingAs($this->stafAkademik);

    // Create data penilaian
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

    // Simulate failed email
    $this->pengajuan->update([
        'status' => 'checking_akademik',
        'email_status' => 'failed',
        'email_error' => 'Previous error',
        'email_attempts' => 1,
    ]);

    $response = $this->postJson('/api/staf-akademik/pengajuan/'.$this->pengajuan->id.'/kirim-konfirmasi');

    $response->assertStatus(202)
        ->assertJson(['status' => 'success'])
        ->assertJsonFragment(['email_attempts' => 2]);

    // Assert job was dispatched
    Queue::assertPushed(KirimEmailKonfirmasiJob::class);

    // Assert attempts incremented and error cleared
    $this->assertDatabaseHas('pengajuan_yudisium', [
        'id' => $this->pengajuan->id,
        'email_status' => 'pending',
        'email_attempts' => 2,
        'email_error' => null,
    ]);
});

test('dashboard can filter by email_status', function () {
    Sanctum::actingAs($this->stafAkademik);

    // Create another mahasiswa and pengajuan with different email_status
    $mahasiswa2 = User::create([
        'nomor_induk' => 'MHS-002',
        'nama' => 'Mahasiswa Test 2',
        'email' => 'mahasiswa2@test.com',
        'role_id' => $this->roleMahasiswa->id,
        'program_studi_id' => $this->prodi->id,
    ]);

    $pengajuan2 = PengajuanYudisium::create([
        'user_id' => $mahasiswa2->id,
        'yudisium_event_id' => $this->event->id,
        'status' => 'checking_akademik',
        'submitted_at' => now(),
        'email_status' => 'sent',
    ]);

    $this->pengajuan->update(['email_status' => 'failed']);

    // Filter by failed
    $response = $this->getJson('/api/staf-akademik/pengajuan?email_status=failed');

    $response->assertStatus(200)
        ->assertJson(['status' => 'success']);

    $data = $response->json('data.data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($this->pengajuan->id);

    // Filter by sent
    $response = $this->getJson('/api/staf-akademik/pengajuan?email_status=sent');

    $response->assertStatus(200);
    $data = $response->json('data.data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($pengajuan2->id);
});
