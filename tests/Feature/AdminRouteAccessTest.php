<?php

use App\Models\Form;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use App\Models\YudisiumEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

dataset('non_super_admin_roles', [
    'mahasiswa',
    'staf_akademik',
    'kaprodi',
    'manit',
    'kadep',
]);

test('unauthenticated users get 401 on GET admin routes', function () {
    $prodi = ProgramStudi::factory()->create();
    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);
    $role = Role::factory()->create(['name' => 'test_user']);
    $user = User::factory()->create([
        'role_id' => $role->id,
        'program_studi_id' => $prodi->id,
    ]);

    $routes = [
        '/api/admin/events',
        "/api/admin/events/{$event->id}",
        '/api/admin/forms',
        "/api/admin/forms/{$form->id}",
        "/api/admin/forms/{$form->id}/fields",
        "/api/admin/program-studi/{$prodi->id}/berita-acara-template",
        '/api/admin/roles',
        '/api/admin/users',
        "/api/admin/users/{$user->id}",
    ];

    foreach ($routes as $route) {
        $this->getJson($route)->assertStatus(401);
    }
});

test('unauthenticated users get 401 on POST admin routes', function () {
    $prodi = ProgramStudi::factory()->create();
    $roleId = Role::factory()->create(['name' => 'kaprodi'])->id;

    $routes = [
        '/api/admin/forms' => [
            'nama_form' => 'Test Form',
            'deskripsi' => 'Test Description',
        ],
        '/api/admin/users' => [
            'nama' => 'Test User',
            'email' => 'test@example.com',
            'role_id' => $roleId,
            'program_studi_id' => $prodi->id,
        ],
    ];

    foreach ($routes as $route => $payload) {
        $this->postJson($route, $payload)->assertStatus(401);
    }
});

test('non super_admin roles get 403 on GET admin routes', function (string $roleName) {
    $prodi = ProgramStudi::factory()->create();
    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    $role = Role::factory()->create(['name' => $roleName]);
    $targetUserRole = Role::factory()->create(['name' => 'target_user_role']);
    $targetUser = User::factory()->create([
        'role_id' => $targetUserRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    $user = User::factory()->create([
        'role_id' => $role->id,
        'program_studi_id' => $prodi->id,
    ]);

    $routes = [
        '/api/admin/events',
        "/api/admin/events/{$event->id}",
        '/api/admin/forms',
        "/api/admin/forms/{$form->id}",
        "/api/admin/forms/{$form->id}/fields",
        "/api/admin/program-studi/{$prodi->id}/berita-acara-template",
        '/api/admin/roles',
        '/api/admin/users',
        "/api/admin/users/{$targetUser->id}",
    ];

    foreach ($routes as $route) {
        actingAs($user, 'sanctum')
            ->getJson($route)
            ->assertStatus(403);
    }
})->with('non_super_admin_roles');

test('non super_admin roles get 403 on POST admin routes', function (string $roleName) {
    $prodi = ProgramStudi::factory()->create();

    $role = Role::factory()->create(['name' => $roleName]);
    $kaprodiRole = $roleName === 'kaprodi' ? $role : Role::factory()->create(['name' => 'kaprodi']);

    $user = User::factory()->create([
        'role_id' => $role->id,
        'program_studi_id' => $prodi->id,
    ]);

    $routes = [
        '/api/admin/forms' => [
            'nama_form' => 'Test Form',
            'deskripsi' => 'Test Description',
        ],
        '/api/admin/users' => [
            'nama' => 'Test User',
            'email' => 'test@example.com',
            'role_id' => $kaprodiRole->id,
            'program_studi_id' => $prodi->id,
        ],
    ];

    foreach ($routes as $route => $payload) {
        actingAs($user, 'sanctum')
            ->postJson($route, $payload)
            ->assertStatus(403);
    }
})->with('non_super_admin_roles');

test('super_admin can access GET admin routes', function () {
    $prodi = ProgramStudi::factory()->create();
    $form = Form::factory()->create();
    $event = YudisiumEvent::factory()->create(['form_id' => $form->id]);

    $role = Role::factory()->create(['name' => 'super_admin']);
    $targetUserRole = Role::factory()->create(['name' => 'target_user']);
    $targetUser = User::factory()->create([
        'role_id' => $targetUserRole->id,
        'program_studi_id' => $prodi->id,
    ]);

    $user = User::factory()->create([
        'role_id' => $role->id,
        'program_studi_id' => $prodi->id,
    ]);

    $routes = [
        '/api/admin/events',
        "/api/admin/events/{$event->id}",
        '/api/admin/forms',
        "/api/admin/forms/{$form->id}",
        "/api/admin/forms/{$form->id}/fields",
        "/api/admin/program-studi/{$prodi->id}/berita-acara-template",
        '/api/admin/roles',
        '/api/admin/users',
        "/api/admin/users/{$targetUser->id}",
    ];

    foreach ($routes as $route) {
        actingAs($user, 'sanctum')
            ->getJson($route)
            ->assertSuccessful();
    }
});

test('super_admin can access POST admin routes', function () {
    $prodi = ProgramStudi::factory()->create();
    $kaprodiRole = Role::factory()->create(['name' => 'kaprodi']);

    $role = Role::factory()->create(['name' => 'super_admin']);
    $user = User::factory()->create([
        'role_id' => $role->id,
        'program_studi_id' => $prodi->id,
    ]);

    $routes = [
        '/api/admin/forms' => [
            'nama_form' => 'Test Form',
            'deskripsi' => 'Test Description',
        ],
        '/api/admin/users' => [
            'nama' => 'Test User',
            'email' => 'test@example.com',
            'role_id' => $kaprodiRole->id,
            'program_studi_id' => $prodi->id,
        ],
    ];

    foreach ($routes as $route => $payload) {
        actingAs($user, 'sanctum')
            ->postJson($route, $payload)
            ->assertSuccessful();
    }
});
