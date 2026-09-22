<?php

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
    $this->role = Role::create([
        'id' => 1,
        'name' => 'super_admin',
    ]);

    $this->prodi = ProgramStudi::create([
        'nama_prodi' => 'Teknik Informatika',
    ]);

    $this->user = User::create([
        'nomor_induk' => 'ADMIN-001',
        'nama' => 'Admin User',
        'email' => 'admin@example.com',
        'role_id' => $this->role->id,
        'program_studi_id' => $this->prodi->id,
    ]);

    $this->form = Form::create([
        'nama_form' => 'Form Yudisium Default',
        'deskripsi' => 'Deskripsi Form',
    ]);
});

test('unauthenticated users cannot access admin events', function () {
    $response = $this->getJson('/api/admin/events');

    $response->assertStatus(401);
});

test('admin can list yudisium events via /api/admin/events and /api/yudisium-events', function () {
    Sanctum::actingAs($this->user);

    YudisiumEvent::create([
        'form_id' => $this->form->id,
        'program_studi_id' => $this->prodi->id,
        'nama_event' => 'Yudisium Periode 1',
        'periode' => '2026',
        'tgl_buka' => '2026-01-01 08:00:00',
        'tgl_tutup' => '2026-01-31 16:00:00',
        'tgl_yudisium' => '2026-02-15',
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/admin/events');
    $response->assertStatus(200)
        ->assertJson(['status' => 'success'])
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['nama_event' => 'Yudisium Periode 1', 'periode' => '2026']);

    $responseAlias = $this->getJson('/api/yudisium-events');
    $responseAlias->assertStatus(200)
        ->assertJson(['status' => 'success'])
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['nama_event' => 'Yudisium Periode 1', 'periode' => '2026']);
});

test('admin can create a new yudisium event', function () {
    Sanctum::actingAs($this->user);

    $payload = [
        'form_id' => $this->form->id,
        'program_studi_id' => $this->prodi->id,
        'nama_event' => 'Yudisium Periode Gasal',
        'periode' => '2025/2026',
        'tgl_buka' => '2026-03-01 08:00:00',
        'tgl_tutup' => '2026-03-20 16:00:00',
        'tgl_yudisium' => '2026-03-31',
        'is_active' => true,
    ];

    $response = $this->postJson('/api/admin/events', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'message' => 'Periode yudisium berhasil dibuat',
            'data' => [
                'nama_event' => 'Yudisium Periode Gasal',
                'periode' => '2025/2026',
                'form_id' => $this->form->id,
                'program_studi_id' => $this->prodi->id,
            ],
        ]);

    $this->assertDatabaseHas('yudisium_events', [
        'nama_event' => 'Yudisium Periode Gasal',
        'periode' => '2025/2026',
    ]);
});

test('admin can create event using Breakdown List API payload (tanggal_buka, tanggal_tutup)', function () {
    Sanctum::actingAs($this->user);

    $payload = [
        'form_id' => $this->form->id,
        'program_studi_id' => $this->prodi->id,
        'nama_event' => 'Yudisium Periode IV',
        'periode' => 'TA 2025/2026',
        'tanggal_buka' => '2026-04-01 08:00:00',
        'tanggal_tutup' => '2026-04-20 16:00:00',
        'is_active' => true,
    ];

    $response = $this->postJson('/api/yudisium-events', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'message' => 'Periode yudisium berhasil dibuat',
            'data' => [
                'nama_event' => 'Yudisium Periode IV',
                'periode' => 'TA 2025/2026',
            ],
        ]);

    $this->assertDatabaseHas('yudisium_events', [
        'nama_event' => 'Yudisium Periode IV',
        'periode' => 'TA 2025/2026',
    ]);
});

test('returns 409 conflict when active event overlaps for the same prodi', function () {
    Sanctum::actingAs($this->user);

    YudisiumEvent::create([
        'form_id' => $this->form->id,
        'program_studi_id' => $this->prodi->id,
        'nama_event' => 'Periode Aktif Pertama',
        'periode' => '2026',
        'tgl_buka' => '2026-05-01 08:00:00',
        'tgl_tutup' => '2026-05-20 16:00:00',
        'is_active' => true,
    ]);

    // Attempt to create overlapping active event for same prodi
    $payload = [
        'form_id' => $this->form->id,
        'program_studi_id' => $this->prodi->id,
        'nama_event' => 'Periode Tumpang Tindih',
        'periode' => '2026',
        'tgl_buka' => '2026-05-15 08:00:00',
        'tgl_tutup' => '2026-05-30 16:00:00',
        'is_active' => true,
    ];

    $response = $this->postJson('/api/yudisium-events', $payload);

    $response->assertStatus(409)
        ->assertJson([
            'status' => 'error',
            'message' => 'Event aktif tumpang tindih untuk program studi ini',
        ]);
});

test('validation fails when tgl_tutup is before tgl_buka', function () {
    Sanctum::actingAs($this->user);

    $payload = [
        'form_id' => $this->form->id,
        'program_studi_id' => $this->prodi->id,
        'nama_event' => 'Periode Invalid',
        'periode' => '2026',
        'tgl_buka' => '2026-03-20 08:00:00',
        'tgl_tutup' => '2026-03-10 16:00:00',
    ];

    $response = $this->postJson('/api/admin/events', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tgl_tutup']);
});

test('admin can view a single yudisium event', function () {
    Sanctum::actingAs($this->user);

    $event = YudisiumEvent::create([
        'form_id' => $this->form->id,
        'program_studi_id' => $this->prodi->id,
        'nama_event' => 'Periode Detail Test',
        'periode' => '2026',
        'tgl_buka' => '2026-04-01 08:00:00',
        'tgl_tutup' => '2026-04-30 16:00:00',
        'is_active' => true,
    ]);

    $response = $this->getJson("/api/admin/events/{$event->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $event->id,
                'nama_event' => 'Periode Detail Test',
                'periode' => '2026',
            ],
        ]);
});

test('admin can update a yudisium event via patch /api/yudisium-events/{id}', function () {
    Sanctum::actingAs($this->user);

    $event = YudisiumEvent::create([
        'form_id' => $this->form->id,
        'program_studi_id' => $this->prodi->id,
        'nama_event' => 'Periode Sebelum Update',
        'periode' => '2026',
        'tgl_buka' => '2026-05-01 08:00:00',
        'tgl_tutup' => '2026-05-31 16:00:00',
        'is_active' => true,
    ]);

    $response = $this->patchJson("/api/yudisium-events/{$event->id}", [
        'tanggal_tutup' => '2026-06-15 16:00:00',
        'is_active' => false,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Periode yudisium berhasil diperbarui',
            'data' => [
                'is_active' => false,
            ],
        ]);

    $this->assertDatabaseHas('yudisium_events', [
        'id' => $event->id,
        'is_active' => false,
    ]);
});

test('admin can delete a yudisium event without submissions', function () {
    Sanctum::actingAs($this->user);

    $event = YudisiumEvent::create([
        'form_id' => $this->form->id,
        'program_studi_id' => $this->prodi->id,
        'nama_event' => 'Periode Akan Dihapus',
        'periode' => '2026',
        'tgl_buka' => '2026-06-01 08:00:00',
        'tgl_tutup' => '2026-06-30 16:00:00',
        'is_active' => false,
    ]);

    $response = $this->deleteJson("/api/admin/events/{$event->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Periode yudisium berhasil dihapus',
        ]);

    $this->assertDatabaseMissing('yudisium_events', [
        'id' => $event->id,
    ]);
});

test('admin cannot delete an event with submissions', function () {
    Sanctum::actingAs($this->user);

    $event = YudisiumEvent::create([
        'form_id' => $this->form->id,
        'program_studi_id' => $this->prodi->id,
        'nama_event' => 'Periode Dengan Pengajuan',
        'periode' => '2026',
        'tgl_buka' => '2026-07-01 08:00:00',
        'tgl_tutup' => '2026-07-31 16:00:00',
        'is_active' => true,
    ]);

    $mahasiswa = User::create([
        'nomor_induk' => 'MHS-001',
        'nama' => 'Mahasiswa Test',
        'email' => 'mhs@example.com',
        'role_id' => $this->role->id,
        'program_studi_id' => $this->prodi->id,
    ]);

    PengajuanYudisium::create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'submitted',
    ]);

    $response = $this->deleteJson("/api/admin/events/{$event->id}");

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'message' => 'Tidak dapat menghapus periode yudisium karena sudah terdapat pengajuan mahasiswa yang terdaftar.',
        ]);

    $this->assertDatabaseHas('yudisium_events', [
        'id' => $event->id,
    ]);
});

test('mahasiswa can retrieve active form with event info (nama_event, periode)', function () {
    $event = YudisiumEvent::create([
        'form_id' => $this->form->id,
        'program_studi_id' => $this->prodi->id,
        'nama_event' => 'Yudisium Periode Gasal',
        'periode' => '2025/2026',
        'tgl_buka' => now()->subDays(2),
        'tgl_tutup' => now()->addDays(10),
        'tgl_yudisium' => now()->addDays(20)->toDateString(),
        'is_active' => true,
    ]);

    $mahasiswa = User::create([
        'nomor_induk' => 'MHS-002',
        'nama' => 'Mahasiswa Aktif',
        'email' => 'mhs2@example.com',
        'role_id' => $this->role->id,
        'program_studi_id' => $this->prodi->id,
    ]);

    Sanctum::actingAs($mahasiswa);

    $response = $this->getJson('/api/pengajuan/active-form');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'event' => [
                    'id' => $event->id,
                    'nama_event' => 'Yudisium Periode Gasal',
                    'periode' => '2025/2026',
                ],
            ],
        ]);
});

test('authenticated users can list program studi via GET /api/program-studi', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/program-studi');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
        ])
        ->assertJsonFragment([
            'nama_prodi' => 'Teknik Informatika',
        ]);
});
