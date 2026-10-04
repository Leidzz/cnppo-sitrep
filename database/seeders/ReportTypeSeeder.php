<?php

namespace Database\Seeders;

use App\Models\ReportType;
use Illuminate\Database\Seeder;

class ReportTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Police Visibility — Date/Time pickers (client-confirmed)
        $policeVisibility = ReportType::updateOrCreate(
            ['name' => 'Police Visibility'],
            ['is_active' => true, 'has_personnel' => false]
        );

        $policeVisibility->templateFields()->updateOrCreate(
            ['field_key' => 'duty_date'],
            ['label' => 'Date of Duty', 'field_type' => 'date', 'is_required' => true, 'sort_order' => 1]
        );
        $policeVisibility->templateFields()->updateOrCreate(
            ['field_key' => 'duty_time'],
            ['label' => 'Time Started', 'field_type' => 'time', 'is_required' => true, 'sort_order' => 2]
        );
        $policeVisibility->templateFields()->updateOrCreate(
            ['field_key' => 'location'],
            ['label' => 'Location', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 3]
        );
        $policeVisibility->templateFields()->updateOrCreate(
            ['field_key' => 'duration_of_duty'],
            ['label' => 'Duration of Duty', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 4]
        );
        $policeVisibility->templateFields()->updateOrCreate(
            ['field_key' => 'activity_conducted'],
            ['label' => 'Activity Conducted', 'field_type' => 'textarea', 'is_required' => true, 'sort_order' => 5]
        );

        // Checkpoint — WHEN is now date+time pickers; carries Team Composition
        $checkpoint = ReportType::updateOrCreate(
            ['name' => 'Checkpoint'],
            ['is_active' => true, 'has_personnel' => true]
        );

        $checkpoint->templateFields()->where('field_key', 'when')->delete();   // remove old text field
        $checkpoint->templateFields()->updateOrCreate(
            ['field_key' => 'what'],
            ['label' => 'What', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 1]
        );
        $checkpoint->templateFields()->updateOrCreate(
            ['field_key' => 'where'],
            ['label' => 'Where', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 2]
        );
        $checkpoint->templateFields()->updateOrCreate(
            ['field_key' => 'when_date'],
            ['label' => 'When — Date', 'field_type' => 'date', 'is_required' => true, 'sort_order' => 3]
        );
        $checkpoint->templateFields()->updateOrCreate(
            ['field_key' => 'when_time'],
            ['label' => 'When — Time', 'field_type' => 'time', 'is_required' => true, 'sort_order' => 4]
        );
        $checkpoint->templateFields()->updateOrCreate(
            ['field_key' => 'how'],
            ['label' => 'How', 'field_type' => 'textarea', 'is_required' => true, 'sort_order' => 5]
        );
        $checkpoint->templateFields()->updateOrCreate(
            ['field_key' => 'remarks'],
            ['label' => 'Remarks', 'field_type' => 'textarea', 'is_required' => false, 'sort_order' => 6]
        );

        // Security Assistance — same pickers; Route Security vs Other stays in narrative
        $securityAssistance = ReportType::updateOrCreate(
            ['name' => 'Security Assistance'],
            ['is_active' => true, 'has_personnel' => false]
        );

        $securityAssistance->templateFields()->where('field_key', 'when')->delete();
        $securityAssistance->templateFields()->updateOrCreate(
            ['field_key' => 'what'],
            ['label' => 'What', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 1]
        );
        $securityAssistance->templateFields()->updateOrCreate(
            ['field_key' => 'where'],
            ['label' => 'Where', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 2]
        );
        $securityAssistance->templateFields()->updateOrCreate(
            ['field_key' => 'when_date'],
            ['label' => 'When — Date', 'field_type' => 'date', 'is_required' => true, 'sort_order' => 3]
        );
        $securityAssistance->templateFields()->updateOrCreate(
            ['field_key' => 'when_time'],
            ['label' => 'When — Time', 'field_type' => 'time', 'is_required' => true, 'sort_order' => 4]
        );
        $securityAssistance->templateFields()->updateOrCreate(
            ['field_key' => 'who'],
            ['label' => 'Who', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 5]
        );
        $securityAssistance->templateFields()->updateOrCreate(
            ['field_key' => 'how'],
            ['label' => 'How', 'field_type' => 'textarea', 'is_required' => true, 'sort_order' => 6]
        );
    }
}
