<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            StatusSeeder::class,
            ReportTypeSeeder::class,
            UnitSeeder::class,
        ]);

        $basud = Unit::where('code', 'BASUD-MPS')->first();

        $reporter = User::factory()->create([
            'name' => 'Test Reporter',
            'email' => 'reporter@example.com',
            'unit_id' => $basud->id,
        ]);
        $reporter->assignRole('reporting-personnel');

        $reviewer = User::factory()->create([
            'name' => 'Test Reviewer',
            'email' => 'reviewer@example.com',
            'unit_id' => $basud->id,
        ]);
        $reviewer->assignRole('command-center');

        $admin = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('administrator');
    }
}
