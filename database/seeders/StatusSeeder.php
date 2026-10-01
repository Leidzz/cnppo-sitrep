<?php

namespace Database\Seeders;

use App\Models\Status;
use App\Models\StatusTransition;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        $draft = Status::firstOrCreate(['name' => 'Draft']);
        $submitted = Status::firstOrCreate(['name' => 'Submitted']);
        $underReview = Status::firstOrCreate(['name' => 'Under Review']);
        $validated = Status::firstOrCreate(['name' => 'Validated', 'is_final' => true]);
        $returned = Status::firstOrCreate(['name' => 'Returned for Correction']);

        $transitions = [
            ['from' => $draft, 'to' => $submitted, 'role' => 'reporting-personnel'],
            ['from' => $submitted, 'to' => $underReview, 'role' => 'command-center'],
            ['from' => $underReview, 'to' => $validated, 'role' => 'command-center'],
            ['from' => $underReview, 'to' => $returned, 'role' => 'command-center'],
            ['from' => $returned, 'to' => $submitted, 'role' => 'reporting-personnel'],
        ];

        foreach ($transitions as $t) {
            StatusTransition::firstOrCreate([
                'from_status_id' => $t['from']->id,
                'to_status_id' => $t['to']->id,
                'allowed_role' => $t['role'],
            ]);
        }
    }
}