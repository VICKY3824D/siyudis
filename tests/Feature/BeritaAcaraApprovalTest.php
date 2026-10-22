<?php

use App\Models\BeritaAcara;
use App\Models\PengajuanYudisium;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\TandaTangan;
use App\Models\User;
use App\Models\YudisiumEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setujuiKaprodi(BeritaAcara $beritaAcara, User $kaprodi): void
{
    $beritaAcara->update([
        'status' => 'approved_kaprodi',
        'approved_kaprodi_by' => $kaprodi->id,
        'approved_kaprodi_at' => now(),
    ]);
}

beforeEach(function () {
    $roles = [];
    foreach (['staf_akademik', 'mahasiswa', 'kaprodi', 'manit', 'kadep'] as $name) {
        $roles[$name] = Role::factory()->create(['name' => $name]);
    }

    $this->prodiA = ProgramStudi::factory()->create(['nama_prodi' => 'Prodi A']);
    $this->prodiB = ProgramStudi::factory()->create(['nama_prodi' => 'Prodi B']);

    $this->stafAkademik = User::factory()->create(['role_id' => $roles['staf_akademik']->id]);
    $this->kaprodiA = User::factory()->create(['role_id' => $roles['kaprodi']->id, 'program_studi_id' => $this->prodiA->id]);
    $this->kaprodiB = User::factory()->create(['role_id' => $roles['kaprodi']->id, 'program_studi_id' => $this->prodiB->id]);
    $this->manit = User::factory()->create(['role_id' => $roles['manit']->id]);
    $this->kadep = User::factory()->create(['role_id' => $roles['kadep']->id]);

    // Semua approver sudah upload e-signature (prasyarat approve)
    foreach ([$this->kaprodiA, $this->kaprodiB, $this->manit, $this->kadep] as $approver) {
        TandaTangan::create(['user_id' => $approver->id, 'file_path' => "tanda-tangan/{$approver->id}.png"]);
    }

    $event = YudisiumEvent::factory()->create();

    // Dua mahasiswa prodi A yang sudah lolos checklist akhir staf akademik
    $this->pengajuan = collect(range(1, 2))->map(function () use ($roles, $event) {
        $mahasiswa = User::factory()->create([
            'role_id' => $roles['mahasiswa']->id,
            'program_studi_id' => $this->prodiA->id,
        ]);

        return PengajuanYudisium::factory()->create([
            'user_id' => $mahasiswa->id,
            'yudisium_event_id' => $event->id,
            'status' => 'final_checked_akademik',
        ]);
    });

    $this->ba = BeritaAcara::factory()->create([
        'yudisium_event_id' => $event->id,
        'program_studi_id' => $this->prodiA->id,
        'diajukan_by' => $this->stafAkademik->id,
        'status' => 'diajukan',
    ]);
    $this->ba->pengajuan()->attach($this->pengajuan->pluck('id'));
});

// --- Kaprodi ---

test('kaprodi hanya melihat berita acara prodinya sendiri', function () {
    $this->actingAs($this->kaprodiA, 'sanctum')
        ->getJson('/api/kaprodi/berita-acara')
        ->assertOk()
        ->assertJsonCount(1, 'data.data');

    $this->actingAs($this->kaprodiB, 'sanctum')
        ->getJson('/api/kaprodi/berita-acara')
        ->assertOk()
        ->assertJsonCount(0, 'data.data');
});

test('kaprodi dapat approve berita acara prodinya', function () {
    $this->actingAs($this->kaprodiA, 'sanctum')
        ->patchJson("/api/kaprodi/berita-acara/{$this->ba->id}/approve")
        ->assertOk()
        ->assertJson(['status' => 'success']);

    $ba = $this->ba->fresh();
    expect($ba->status)->toBe('approved_kaprodi')
        ->and($ba->approved_kaprodi_by)->toBe($this->kaprodiA->id)
        ->and($ba->approved_kaprodi_at)->not->toBeNull();
});

test('kaprodi tidak dapat approve berita acara prodi lain', function () {
    $this->actingAs($this->kaprodiB, 'sanctum')
        ->patchJson("/api/kaprodi/berita-acara/{$this->ba->id}/approve")
        ->assertForbidden();

    expect($this->ba->fresh()->status)->toBe('diajukan');
});

test('kaprodi tidak dapat approve tanpa e-signature', function () {
    $this->kaprodiA->tandaTangan()->delete();

    $this->actingAs($this->kaprodiA, 'sanctum')
        ->patchJson("/api/kaprodi/berita-acara/{$this->ba->id}/approve")
        ->assertStatus(422);

    expect($this->ba->fresh()->status)->toBe('diajukan');
});

test('kaprodi tidak dapat approve dua kali', function () {
    $url = "/api/kaprodi/berita-acara/{$this->ba->id}/approve";

    $this->actingAs($this->kaprodiA, 'sanctum')->patchJson($url)->assertOk();
    $this->actingAs($this->kaprodiA, 'sanctum')->patchJson($url)->assertStatus(409);
});

test('kaprodi reject mengembalikan berita acara ke staf akademik', function () {
    $this->actingAs($this->kaprodiA, 'sanctum')
        ->patchJson("/api/kaprodi/berita-acara/{$this->ba->id}/reject", [
            'catatan_revisi_internal' => 'Dokumen SKS belum sesuai',
        ])
        ->assertOk();

    $ba = $this->ba->fresh();
    expect($ba->status)->toBe('ditolak')
        ->and($ba->catatan_revisi_internal)->toBe('Dokumen SKS belum sesuai')
        ->and($ba->pengajuan()->count())->toBe(0);

    // Mahasiswa kembali ke tahap staf akademik dan bisa dimasukkan ke berita acara baru
    $this->pengajuan->each(fn ($p) => expect($p->fresh()->status)->toBe('checking_akademik'));
});

test('reject wajib mengisi catatan_revisi_internal', function () {
    $this->actingAs($this->kaprodiA, 'sanctum')
        ->patchJson("/api/kaprodi/berita-acara/{$this->ba->id}/reject", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['catatan_revisi_internal']);
});

test('kaprodi tidak dapat reject setelah dirinya sudah approve', function () {
    setujuiKaprodi($this->ba, $this->kaprodiA);

    $this->actingAs($this->kaprodiA, 'sanctum')
        ->patchJson("/api/kaprodi/berita-acara/{$this->ba->id}/reject", ['catatan_revisi_internal' => 'salah'])
        ->assertStatus(409);
});

test('role lain tidak dapat mengakses endpoint approval', function () {
    $this->actingAs($this->manit, 'sanctum')->getJson('/api/kaprodi/berita-acara')->assertForbidden();
    $this->actingAs($this->stafAkademik, 'sanctum')->getJson('/api/manit/berita-acara')->assertForbidden();
    $this->actingAs($this->kaprodiA, 'sanctum')->getJson('/api/kadep/berita-acara')->assertForbidden();
});

// --- Manit & Kadep (paralel) ---

test('manit tidak dapat approve sebelum kaprodi approve', function () {
    $this->actingAs($this->manit, 'sanctum')
        ->patchJson("/api/manit/berita-acara/{$this->ba->id}/approve")
        ->assertStatus(409);
});

test('manit hanya melihat berita acara yang menunggu approval-nya', function () {
    $this->actingAs($this->manit, 'sanctum')
        ->getJson('/api/manit/berita-acara')
        ->assertOk()
        ->assertJsonCount(0, 'data.data');

    setujuiKaprodi($this->ba, $this->kaprodiA);

    $this->actingAs($this->manit, 'sanctum')
        ->getJson('/api/manit/berita-acara')
        ->assertJsonCount(1, 'data.data');

    $this->ba->update(['approved_manit_by' => $this->manit->id, 'approved_manit_at' => now()]);

    $this->actingAs($this->manit, 'sanctum')
        ->getJson('/api/manit/berita-acara')
        ->assertJsonCount(0, 'data.data');
});

test('manit tidak dapat membuka detail berita acara yang belum disetujui kaprodi', function () {
    $this->actingAs($this->manit, 'sanctum')
        ->getJson("/api/manit/berita-acara/{$this->ba->id}")
        ->assertStatus(409);
});

test('approve manit saja belum menyelesaikan berita acara', function () {
    setujuiKaprodi($this->ba, $this->kaprodiA);

    $this->actingAs($this->manit, 'sanctum')
        ->patchJson("/api/manit/berita-acara/{$this->ba->id}/approve")
        ->assertOk();

    $ba = $this->ba->fresh();
    expect($ba->status)->toBe('approved_kaprodi')
        ->and($ba->approved_manit_at)->not->toBeNull()
        ->and($ba->approved_kadep_at)->toBeNull();
});

test('berita acara completed setelah manit dan kadep approve, urutan bebas', function () {
    setujuiKaprodi($this->ba, $this->kaprodiA);

    $this->actingAs($this->kadep, 'sanctum')
        ->patchJson("/api/kadep/berita-acara/{$this->ba->id}/approve")
        ->assertOk();
    expect($this->ba->fresh()->status)->toBe('approved_kaprodi');

    $this->actingAs($this->manit, 'sanctum')
        ->patchJson("/api/manit/berita-acara/{$this->ba->id}/approve")
        ->assertOk();

    $ba = $this->ba->fresh();
    expect($ba->status)->toBe('completed')
        ->and($ba->approved_manit_at)->not->toBeNull()
        ->and($ba->approved_kadep_at)->not->toBeNull();

    $this->pengajuan->each(fn ($p) => expect($p->fresh()->status)->toBe('completed'));
});

test('manit tidak dapat approve dua kali', function () {
    setujuiKaprodi($this->ba, $this->kaprodiA);
    $this->ba->update(['approved_manit_by' => $this->manit->id, 'approved_manit_at' => now()]);

    $this->actingAs($this->manit, 'sanctum')
        ->patchJson("/api/manit/berita-acara/{$this->ba->id}/approve")
        ->assertStatus(409);
});

test('manit tidak dapat approve tanpa e-signature', function () {
    setujuiKaprodi($this->ba, $this->kaprodiA);
    $this->manit->tandaTangan()->delete();

    $this->actingAs($this->manit, 'sanctum')
        ->patchJson("/api/manit/berita-acara/{$this->ba->id}/approve")
        ->assertStatus(422);
});

test('reject kadep mereset semua approval dan melepas mahasiswa dari berita acara', function () {
    setujuiKaprodi($this->ba, $this->kaprodiA);
    $this->ba->update(['approved_manit_by' => $this->manit->id, 'approved_manit_at' => now()]);

    $this->actingAs($this->kadep, 'sanctum')
        ->patchJson("/api/kadep/berita-acara/{$this->ba->id}/reject", [
            'catatan_revisi_internal' => 'Nilai similarity index perlu dicek ulang',
        ])
        ->assertOk();

    $ba = $this->ba->fresh();
    expect($ba->status)->toBe('ditolak')
        ->and($ba->catatan_revisi_internal)->toBe('Nilai similarity index perlu dicek ulang')
        ->and($ba->approved_kaprodi_at)->toBeNull()
        ->and($ba->approved_manit_at)->toBeNull()
        ->and($ba->approved_kadep_at)->toBeNull()
        ->and($ba->pengajuan()->count())->toBe(0);

    $this->pengajuan->each(fn ($p) => expect($p->fresh()->status)->toBe('checking_akademik'));
});

test('berita acara yang sudah completed tidak dapat direject', function () {
    setujuiKaprodi($this->ba, $this->kaprodiA);
    $this->ba->update([
        'status' => 'completed',
        'approved_manit_by' => $this->manit->id,
        'approved_manit_at' => now(),
        'approved_kadep_by' => $this->kadep->id,
        'approved_kadep_at' => now(),
    ]);

    $this->actingAs($this->manit, 'sanctum')
        ->patchJson("/api/manit/berita-acara/{$this->ba->id}/reject", ['catatan_revisi_internal' => 'salah'])
        ->assertStatus(409);
});
