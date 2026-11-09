<?php

use App\Models\BeritaAcara;
use App\Models\Form;
use App\Models\PengajuanYudisium;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use App\Models\YudisiumEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

test('cannot delete user with submitted pengajuan', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);
    $prodi = ProgramStudi::factory()->create();
    $user = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    PengajuanYudisium::factory()->create([
        'user_id' => $user->id,
        'yudisium_event_id' => $event->id,
        'status' => 'submitted',
    ]);

    actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/admin/users/{$user->id}")
        ->assertStatus(409)
        ->assertJson([
            'status' => 'error',
            'message' => 'User masih memiliki pengajuan yudisium yang sedang berjalan',
        ]);

    assertDatabaseHas('users', ['id' => $user->id]);
});

test('cannot delete user with reset_to_akademik pengajuan', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);
    $prodi = ProgramStudi::factory()->create();
    $user = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    PengajuanYudisium::factory()->create([
        'user_id' => $user->id,
        'yudisium_event_id' => $event->id,
        'status' => 'reset_to_akademik',
    ]);

    actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/admin/users/{$user->id}")
        ->assertStatus(409)
        ->assertJson([
            'status' => 'error',
            'message' => 'User masih memiliki pengajuan yudisium yang sedang berjalan',
        ]);

    assertDatabaseHas('users', ['id' => $user->id]);
});

test('can delete user with only completed pengajuan and pengajuan is also deleted', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);
    $prodi = ProgramStudi::factory()->create();
    $user = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $user->id,
        'yudisium_event_id' => $event->id,
        'status' => 'completed',
    ]);

    actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/admin/users/{$user->id}")
        ->assertStatus(204);

    assertDatabaseMissing('users', ['id' => $user->id]);
    assertDatabaseMissing('pengajuan_yudisium', ['id' => $pengajuan->id]);
});

test('can delete user without pengajuan', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);
    $prodi = ProgramStudi::factory()->create();
    $user = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/admin/users/{$user->id}")
        ->assertStatus(204);

    assertDatabaseMissing('users', ['id' => $user->id]);
});

test('can delete staf akademik who checked completed pengajuan', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

    $stafRole = Role::factory()->create(['name' => 'staf_akademik']);
    $prodi = ProgramStudi::factory()->create();
    $stafAkademik = User::factory()->create([
        'role_id' => $stafRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    $mahasiswaRole = Role::factory()->create(['name' => 'mahasiswa']);
    $mahasiswa = User::factory()->create([
        'role_id' => $mahasiswaRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    $pengajuan = PengajuanYudisium::factory()->create([
        'user_id' => $mahasiswa->id,
        'yudisium_event_id' => $event->id,
        'status' => 'completed',
        'checked_akademik_by' => $stafAkademik->id,
    ]);

    actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/admin/users/{$stafAkademik->id}")
        ->assertStatus(204);

    assertDatabaseMissing('users', ['id' => $stafAkademik->id]);
    assertDatabaseHas('pengajuan_yudisium', [
        'id' => $pengajuan->id,
        'checked_akademik_by' => null,
    ]);
});

test('can delete staf akademik who submitted berita acara', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

    $stafRole = Role::factory()->create(['name' => 'staf_akademik']);
    $prodi = ProgramStudi::factory()->create();
    $stafAkademik = User::factory()->create([
        'role_id' => $stafRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    $beritaAcara = BeritaAcara::create([
        'yudisium_event_id' => $event->id,
        'program_studi_id' => $prodi->id,
        'status' => 'diajukan',
        'diajukan_by' => $stafAkademik->id,
        'diajukan_at' => now(),
    ]);

    actingAs($superAdmin, 'sanctum')
        ->deleteJson("/api/admin/users/{$stafAkademik->id}")
        ->assertStatus(204);

    assertDatabaseMissing('users', ['id' => $stafAkademik->id]);
    assertDatabaseHas('berita_acara', [
        'id' => $beritaAcara->id,
        'diajukan_by' => null,
    ]);
});
