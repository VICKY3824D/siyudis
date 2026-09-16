<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = ['mahasiswa', 'staf_akademik', 'kaprodi', 'manit', 'kadep', 'super_admin'];

        foreach ($defaults as $name) {
            Role::firstOrCreate(['name' => $name], ['is_system' => true]);
        }
    }
}