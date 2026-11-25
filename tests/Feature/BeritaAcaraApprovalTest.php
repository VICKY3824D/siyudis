<?php

use App\Models\BeritaAcara;
use App\Models\BeritaAcaraApproval;
use App\Models\PengajuanYudisium;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\TandaTangan;
use App\Models\User;
use App\Models\YudisiumEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function setujuiKaprodi(BeritaAcara $beritaAcara, User $kaprodi): void
{
    $beritaAcara->update(['status' => 'approved_kaprodi']);
    setujuiRole($beritaAcara, $kaprodi, 'kaprodi');
}

function setujuiRole(BeritaAcara $beritaAcara, User $user, string $role): void
{
    BeritaAcaraApproval::factory()->create([
        'berita_acara_id' => $beritaAcara->id,
        'cycle' => $beritaAcara->current_cycle ?? 1,
        'role' => $role,
        'user_id' => $user->id,
        'approver_nama' => $user->nama,
        'status' => BeritaAcaraApproval::STATUS_APPROVED,
        'acted_at' => now(),
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
    $approval = $ba->approvalForRole('kaprodi');

    expect($ba->status)->toBe('approved_kaprodi')
        ->and($ba->current_cycle)->toBe(1)
        ->and($approval)->not->toBeNull()
        ->and($approval->user_id)->toBe($this->kaprodiA->id)
        ->and($approval->approver_nama)->toBe($this->kaprodiA->nama)
        ->and($approval->status)->toBe(BeritaAcaraApproval::STATUS_APPROVED)
        ->and($approval->cycle)->toBe(1)
        ->and($approval->acted_at)->not->toBeNull();
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
    $rejectRecord = $ba->approvalForRole('kaprodi', 1);

    expect($ba->status)->toBe('ditolak')
        ->and($ba->current_cycle)->toBe(2)
        ->and($ba->catatan_revisi_internal)->toBe('Dokumen SKS belum sesuai')
        ->and($ba->pengajuan()->count())->toBe(0)
        ->and($rejectRecord)->not->toBeNull()
        ->and($rejectRecord->status)->toBe(BeritaAcaraApproval::STATUS_REJECTED)
        ->and($rejectRecord->catatan)->toBe('Dokumen SKS belum sesuai')
        ->and($rejectRecord->user_id)->toBe($this->kaprodiA->id);

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

    setujuiRole($this->ba, $this->manit, 'manit');

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
        ->and($ba->hasApproved('manit'))->toBeTrue()
        ->and($ba->hasApproved('kadep'))->toBeFalse();
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
        ->and($ba->hasApproved('manit'))->toBeTrue()
        ->and($ba->hasApproved('kadep'))->toBeTrue();

    $this->pengajuan->each(fn ($p) => expect($p->fresh()->status)->toBe('completed'));
});

test('manit tidak dapat approve dua kali', function () {
    setujuiKaprodi($this->ba, $this->kaprodiA);
    setujuiRole($this->ba, $this->manit, 'manit');

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

test('reject kadep menyimpan riwayat cycle dan melepas mahasiswa dari berita acara', function () {
    setujuiKaprodi($this->ba, $this->kaprodiA);
    setujuiRole($this->ba, $this->manit, 'manit');

    $this->actingAs($this->kadep, 'sanctum')
        ->patchJson("/api/kadep/berita-acara/{$this->ba->id}/reject", [
            'catatan_revisi_internal' => 'Nilai similarity index perlu dicek ulang',
        ])
        ->assertOk();

    $ba = $this->ba->fresh();
    expect($ba->status)->toBe('ditolak')
        ->and($ba->current_cycle)->toBe(2)
        ->and($ba->catatan_revisi_internal)->toBe('Nilai similarity index perlu dicek ulang')
        ->and($ba->hasApproved('kaprodi'))->toBeFalse()
        ->and($ba->hasApproved('manit'))->toBeFalse()
        ->and($ba->hasApproved('kadep'))->toBeFalse()
        ->and($ba->hasApproved('kaprodi', 1))->toBeTrue()
        ->and($ba->hasApproved('manit', 1))->toBeTrue()
        ->and($ba->approvalForRole('kadep', 1)?->status)->toBe(BeritaAcaraApproval::STATUS_REJECTED)
        ->and($ba->approvalForRole('kadep', 1)?->catatan)->toBe('Nilai similarity index perlu dicek ulang')
        ->and($ba->pengajuan()->count())->toBe(0);

    $this->pengajuan->each(fn ($p) => expect($p->fresh()->status)->toBe('checking_akademik'));
});

test('berita acara yang sudah completed tidak dapat direject', function () {
    setujuiKaprodi($this->ba, $this->kaprodiA);
    $this->ba->update(['status' => 'completed']);
    setujuiRole($this->ba, $this->manit, 'manit');
    setujuiRole($this->ba, $this->kadep, 'kadep');

    $this->actingAs($this->manit, 'sanctum')
        ->patchJson("/api/manit/berita-acara/{$this->ba->id}/reject", ['catatan_revisi_internal' => 'salah'])
        ->assertStatus(409);
});

// --- Snapshot Signature, Nama Approver & Multi-Cycle ---

test('approve menyimpan snapshot signature dan nama di berita_acara_approvals yang tetap bertahan meski pejabat mengganti e-signature', function () {
    Storage::fake('public');

    $kaprodiPath = "tanda-tangan/{$this->kaprodiA->id}.png";
    $manitPath = "tanda-tangan/{$this->manit->id}.png";
    $kadepPath = "tanda-tangan/{$this->kadep->id}.png";

    Storage::disk('public')->put($kaprodiPath, 'kaprodi-old-bytes');
    Storage::disk('public')->put($manitPath, 'manit-old-bytes');
    Storage::disk('public')->put($kadepPath, 'kadep-old-bytes');

    $this->actingAs($this->kaprodiA, 'sanctum')
        ->patchJson("/api/kaprodi/berita-acara/{$this->ba->id}/approve")
        ->assertOk();

    $this->actingAs($this->manit, 'sanctum')
        ->patchJson("/api/manit/berita-acara/{$this->ba->id}/approve")
        ->assertOk();

    $this->actingAs($this->kadep, 'sanctum')
        ->patchJson("/api/kadep/berita-acara/{$this->ba->id}/approve")
        ->assertOk();

    $ba = $this->ba->fresh('approvals');
    $kaprodiApproval = $ba->approvalForRole('kaprodi');
    $manitApproval = $ba->approvalForRole('manit');
    $kadepApproval = $ba->approvalForRole('kadep');

    expect($kaprodiApproval->approver_nama)->toBe($this->kaprodiA->nama)
        ->and($kaprodiApproval->cycle)->toBe(1)
        ->and($kaprodiApproval->signature_path)->toStartWith('tanda-tangan-snapshots/')
        ->and($manitApproval->approver_nama)->toBe($this->manit->nama)
        ->and($manitApproval->signature_path)->toStartWith('tanda-tangan-snapshots/')
        ->and($kadepApproval->approver_nama)->toBe($this->kadep->nama)
        ->and($kadepApproval->signature_path)->toStartWith('tanda-tangan-snapshots/');

    Storage::disk('public')->assertExists($kaprodiApproval->signature_path);
    Storage::disk('public')->assertExists($manitApproval->signature_path);
    Storage::disk('public')->assertExists($kadepApproval->signature_path);

    // Kaprodi mengganti e-signature -> file lama terhapus, namun snapshot berita acara tetap ada & isinya utuh
    $this->actingAs($this->kaprodiA, 'sanctum')
        ->put('/api/signature/me', [
            'file' => UploadedFile::fake()->image('ttd-baru.png'),
        ])
        ->assertOk();

    Storage::disk('public')->assertMissing($kaprodiPath);
    Storage::disk('public')->assertExists($kaprodiApproval->signature_path);
    expect(Storage::disk('public')->get($kaprodiApproval->signature_path))->toBe('kaprodi-old-bytes');
});

test('approval di cycle baru terisolasi dari riwayat approval cycle sebelumnya', function () {
    Storage::fake('public');

    Storage::disk('public')->put("tanda-tangan/{$this->kaprodiA->id}.png", 'kaprodi-c1');
    Storage::disk('public')->put("tanda-tangan/{$this->manit->id}.png", 'manit-c1');

    $this->actingAs($this->kaprodiA, 'sanctum')
        ->patchJson("/api/kaprodi/berita-acara/{$this->ba->id}/approve")
        ->assertOk();

    $this->actingAs($this->manit, 'sanctum')
        ->patchJson("/api/manit/berita-acara/{$this->ba->id}/approve")
        ->assertOk();

    $kaprodiC1Snapshot = $this->ba->fresh()->approvalForRole('kaprodi', 1)->signature_path;

    $this->actingAs($this->kadep, 'sanctum')
        ->patchJson("/api/kadep/berita-acara/{$this->ba->id}/reject", [
            'catatan_revisi_internal' => 'Perlu perbaikan data mahasiswa',
        ])
        ->assertOk();

    // Riwayat & snapshot cycle 1 tetap tersimpan, sementara berita acara masuk cycle 2
    $ba = $this->ba->fresh('approvals');
    expect($ba->current_cycle)->toBe(2)
        ->and($ba->approvals)->toHaveCount(3);
    Storage::disk('public')->assertExists($kaprodiC1Snapshot);

    // Simulasi cycle 2 diajukan kembali dan disetujui kaprodi
    $ba->update(['status' => 'diajukan']);
    Storage::disk('public')->put("tanda-tangan/{$this->kaprodiA->id}.png", 'kaprodi-c2');

    $this->actingAs($this->kaprodiA, 'sanctum')
        ->patchJson("/api/kaprodi/berita-acara/{$ba->id}/approve")
        ->assertOk();

    // Manit di cycle 2 tetap melihat BA menunggu approval-nya meski pernah approve di cycle 1
    $this->actingAs($this->manit, 'sanctum')
        ->getJson('/api/manit/berita-acara')
        ->assertOk()
        ->assertJsonCount(1, 'data.data');

    $this->actingAs($this->manit, 'sanctum')
        ->patchJson("/api/manit/berita-acara/{$ba->id}/approve")
        ->assertOk();

    $baCycle2 = $ba->fresh('approvals');
    expect($baCycle2->approvals()->where('cycle', 2)->count())->toBe(2)
        ->and(Storage::disk('public')->get($baCycle2->approvalForRole('kaprodi', 2)->signature_path))->toBe('kaprodi-c2')
        ->and(Storage::disk('public')->get($baCycle2->approvalForRole('kaprodi', 1)->signature_path))->toBe('kaprodi-c1');
});
