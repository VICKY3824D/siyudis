<?php

use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('super admin dapat melihat template berita acara', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
    $prodi = ProgramStudi::factory()->create(['berita_acara_template' => '<h1>Test Template</h1>']);

    actingAs($superAdmin, 'sanctum')
        ->getJson("/api/admin/program-studi/{$prodi->id}/berita-acara-template")
        ->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'template' => '<h1>Test Template</h1>',
            ],
        ]);
});

test('super admin dapat mengupdate template berita acara', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
    $prodi = ProgramStudi::factory()->create();

    $newTemplate = '<h1>{{prodi}}</h1><p>{{periode}}</p>';

    actingAs($superAdmin, 'sanctum')
        ->putJson("/api/admin/program-studi/{$prodi->id}/berita-acara-template", [
            'template' => $newTemplate,
        ])
        ->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Template berita acara berhasil diperbarui',
        ]);

    expect($prodi->fresh()->berita_acara_template)->toBe($newTemplate);
});

test('template dengan tag script ditolak', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
    $prodi = ProgramStudi::factory()->create();

    actingAs($superAdmin, 'sanctum')
        ->putJson("/api/admin/program-studi/{$prodi->id}/berita-acara-template", [
            'template' => '<script>alert("xss")</script>',
        ])
        ->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'message' => 'Template tidak boleh mengandung tag <script>',
        ]);
});

test('template dengan placeholder tidak dikenal ditolak', function () {
    $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
    $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
    $prodi = ProgramStudi::factory()->create();

    actingAs($superAdmin, 'sanctum')
        ->putJson("/api/admin/program-studi/{$prodi->id}/berita-acara-template", [
            'template' => '<h1>{{invalid_placeholder}}</h1>',
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'status' => 'error',
        ]);
});
