<?php

namespace Database\Seeders;

use App\Models\ReportType;
use Illuminate\Database\Seeder;

class ReportTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Police Visibility
        $policeVisibility = ReportType::firstOrCreate(
            ['name' => 'Police Visibility'],
            ['is_active' => true, 'has_personnel' => false]
        );

        $policeVisibility->templateFields()->firstOrCreate(
            ['field_key' => 'location'],
            ['label' => 'Location', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 1]
        );
        $policeVisibility->templateFields()->firstOrCreate(
            ['field_key' => 'duration_of_duty'],
            ['label' => 'Duration of Duty', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 2]
        );
        $policeVisibility->templateFields()->firstOrCreate(
            ['field_key' => 'activity_conducted'],
            ['label' => 'Activity Conducted', 'field_type' => 'textarea', 'is_required' => true, 'sort_order' => 3]
        );

        // Checkpoint — carries the Team Composition (client-confirmed),
        // so the flag is set on (re)seed.
        $checkpoint = ReportType::updateOrCreate(
            ['name' => 'Checkpoint'],
            ['is_active' => true, 'has_personnel' => true]
        );

        $checkpoint->templateFields()->firstOrCreate(
            ['field_key' => 'what'],
            ['label' => 'What', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 1]
        );
        $checkpoint->templateFields()->firstOrCreate(
            ['field_key' => 'where'],
            ['label' => 'Where', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 2]
        );
        $checkpoint->templateFields()->firstOrCreate(
            ['field_key' => 'when'],
            ['label' => 'When', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 3]
        );
        $checkpoint->templateFields()->firstOrCreate(
            ['field_key' => 'how'],
            ['label' => 'How', 'field_type' => 'textarea', 'is_required' => true, 'sort_order' => 4]
        );
        $checkpoint->templateFields()->firstOrCreate(
            ['field_key' => 'remarks'],
            ['label' => 'Remarks', 'field_type' => 'textarea', 'is_required' => false, 'sort_order' => 5]
        );

        // Security Assistance
        $securityAssistance = ReportType::firstOrCreate(
            ['name' => 'Security Assistance'],
            ['is_active' => true, 'has_personnel' => false]
        );

        $securityAssistance->templateFields()->firstOrCreate(
            ['field_key' => 'what'],
            ['label' => 'What', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 1]
        );
        $securityAssistance->templateFields()->firstOrCreate(
            ['field_key' => 'where'],
            ['label' => 'Where', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 2]
        );
        $securityAssistance->templateFields()->firstOrCreate(
            ['field_key' => 'when'],
            ['label' => 'When', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 3]
        );
        $securityAssistance->templateFields()->firstOrCreate(
            ['field_key' => 'who'],
            ['label' => 'Who', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 4]
        );
        $securityAssistance->templateFields()->firstOrCreate(
            ['field_key' => 'how'],
            ['label' => 'How', 'field_type' => 'textarea', 'is_required' => true, 'sort_order' => 5]
        );
    }
}
