<?php

use App\Models\Form;
use App\Models\FormField;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use App\Models\YudisiumEvent;
use Database\Seeders\FormFieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('form field seeder mengisi seluruh field referensi secara idempoten', function () {
    $this->seed(FormFieldSeeder::class);
    $firstCount = FormField::count();

    $this->seed(FormFieldSeeder::class);

    // 20 field umum + 4 field Sertifikat Kompetensi (per prodi) + 1 Surat Bebas Lab (khusus TRI) = 25 baris
    expect(Form::count())->toBe(1)
        ->and(ProgramStudi::count())->toBe(4)
        ->and(FormField::count())->toBe(25)
        ->and(FormField::count())->toBe($firstCount);

    expect(FormField::where('field_name', 'Tautan (Link) Video Presentasi Tugas Akhir')->value('data_type'))->toBe('link')
        ->and(FormField::where('field_name', 'Pas Photo')->value('data_type'))->toBe('image')
        ->and(FormField::where('field_name', 'Transkrip Nilai D3')->value('is_required'))->toBeFalse();
});

test('active form menampilkan aturan wajib prodi TRI dan opsional prodi lain dengan benar', function () {
    $this->seed(FormFieldSeeder::class);

    $form = Form::firstOrFail();
    YudisiumEvent::factory()->create([
        'form_id' => $form->id,
        'tgl_buka' => now()->subDay(),
        'tgl_tutup' => now()->addDays(7),
        'is_active' => true,
    ]);

    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);
    $triProdi = ProgramStudi::where('nama_prodi', 'like', '%(TRI)%')->firstOrFail();
    $trplProdi = ProgramStudi::where('nama_prodi', 'like', '%(TRPL)%')->firstOrFail();

    $mahasiswaTri = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $triProdi->id,
    ]);
    $mahasiswaTrpl = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $trplProdi->id,
    ]);

    $triFields = collect(
        $this->actingAs($mahasiswaTri, 'sanctum')
            ->getJson('/api/pengajuan/active-form')
            ->assertOk()
            ->json('data.fields')
    );

    expect($triFields)->toHaveCount(22)
        ->and($triFields->firstWhere('field_name', 'Sertifikat Kompetensi')['is_required'])->toBeTrue()
        ->and($triFields->firstWhere('field_name', 'Surat Bebas Lab')['is_required'])->toBeTrue();

    $trplFields = collect(
        $this->actingAs($mahasiswaTrpl, 'sanctum')
            ->getJson('/api/pengajuan/active-form')
            ->assertOk()
            ->json('data.fields')
    );

    expect($trplFields)->toHaveCount(21)
        ->and($trplFields->firstWhere('field_name', 'Sertifikat Kompetensi')['is_required'])->toBeFalse()
        ->and($trplFields->firstWhere('field_name', 'Surat Bebas Lab'))->toBeNull();
});
