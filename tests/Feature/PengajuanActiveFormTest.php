<?php

use App\Models\Form;
use App\Models\FormField;
use App\Models\PengajuanYudisium;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use App\Models\YudisiumEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $role = Role::factory()->create(['name' => 'mahasiswa']);
    $prodi = ProgramStudi::factory()->create(['nama_prodi' => 'Prodi A']);

    $this->mahasiswa = User::factory()->create([
        'role_id' => $role->id,
        'program_studi_id' => $prodi->id,
    ]);

    $this->form = Form::factory()->create();

    $this->field = FormField::create([
        'form_id' => $this->form->id,
        'field_name' => 'IPK',
        'data_type' => 'freetext',
        'is_required' => false,
        'is_visible' => true,
        'order_position' => 1,
    ]);

    // Helper pembuat event dengan rentang tanggal tertentu
    $this->buatEvent = fn (string $nama, $buka, $tutup, bool $aktif = true) => YudisiumEvent::factory()->create([
        'form_id' => $this->form->id,
        'nama_event' => $nama,
        'tgl_buka' => $buka,
        'tgl_tutup' => $tutup,
        'tgl_yudisium' => now()->addMonth(),
        'is_active' => $aktif,
    ]);
});

// --- GET /pengajuan/active-form ---

test('active form tampil saat event aktif dan masih dalam rentang tanggal', function () {
    $event = ($this->buatEvent)('Berlangsung', now()->subDays(2), now()->addDays(10));

    $this->actingAs($this->mahasiswa, 'sanctum')
        ->getJson('/api/pengajuan/active-form')
        ->assertOk()
        ->assertJsonPath('data.event.id', $event->id);
});

test('active form 404 saat event aktif tapi sudah kedaluwarsa (BUG-004)', function () {
    ($this->buatEvent)('Kedaluwarsa', now()->subDays(10), now()->subDay());

    $this->actingAs($this->mahasiswa, 'sanctum')
        ->getJson('/api/pengajuan/active-form')
        ->assertNotFound()
        ->assertJson(['status' => 'error', 'message' => 'Belum ada periode yudisium yang aktif']);
});

test('active form 404 saat event aktif tapi belum dibuka', function () {
    ($this->buatEvent)('Belum dibuka', now()->addDays(2), now()->addDays(10));

    $this->actingAs($this->mahasiswa, 'sanctum')
        ->getJson('/api/pengajuan/active-form')
        ->assertNotFound();
});

test('active form 404 saat event dalam rentang tapi is_active false', function () {
    ($this->buatEvent)('Nonaktif', now()->subDay(), now()->addDays(10), false);

    $this->actingAs($this->mahasiswa, 'sanctum')
        ->getJson('/api/pengajuan/active-form')
        ->assertNotFound();
});

test('active form memilih event yang berlangsung, bukan yang kedaluwarsa', function () {
    ($this->buatEvent)('Lama', now()->subDays(30), now()->subDays(20));
    $berlangsung = ($this->buatEvent)('Sekarang', now()->subDay(), now()->addDays(5));

    $this->actingAs($this->mahasiswa, 'sanctum')
        ->getJson('/api/pengajuan/active-form')
        ->assertOk()
        ->assertJsonPath('data.event.id', $berlangsung->id);
});

// --- POST /pengajuan (event harus sedang berlangsung) ---

test('submit pengajuan ke event yang sedang berlangsung berhasil', function () {
    $event = ($this->buatEvent)('Berlangsung', now()->subDay(), now()->addDays(5));

    $this->actingAs($this->mahasiswa, 'sanctum')
        ->postJson('/api/pengajuan', [
            'yudisium_event_id' => $event->id,
            'field_values' => [['form_field_id' => $this->field->id, 'value' => '3.75']],
        ])
        ->assertCreated();
});

test('submit pengajuan ke event kedaluwarsa ditolak', function () {
    $event = ($this->buatEvent)('Kedaluwarsa', now()->subDays(10), now()->subDay());

    $this->actingAs($this->mahasiswa, 'sanctum')
        ->postJson('/api/pengajuan', [
            'yudisium_event_id' => $event->id,
            'field_values' => [['form_field_id' => $this->field->id, 'value' => '3.75']],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['yudisium_event_id']);

    expect(PengajuanYudisium::where('user_id', $this->mahasiswa->id)->exists())->toBeFalse();
});
