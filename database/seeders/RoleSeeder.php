<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'reporting-personnel']);
        Role::firstOrCreate(['name' => 'command-center']);
        Role::firstOrCreate(['name' => 'administrator']);
    }
}