<?php

use App\Models\Attachment;
use App\Models\ReportType;
use App\Models\Sitrep;
use App\Models\Status;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\ReportTypeSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, StatusSeeder::class, ReportTypeSeeder::class, UnitSeeder::class]);
    Storage::fake('sitrep_photos');
});

function sitrepWithPhoto(User $owner): Attachment
{
    $checkpoint = ReportType::where('name', 'Checkpoint')->first();

    Sitrep::create([
        'reference_number' => 'SITREP-2026-9999',
        'unit_id' => $owner->unit_id,
        'report_type_id' => $checkpoint->id,
        'current_status_id' => Status::where('name', 'Submitted')->first()->id,
        'created_by' => $owner->id,
        'dynamic_fields' => [],
        'submitted_at' => now(),
    ]);

    $path = UploadedFile::fake()->image('evidence.jpg', 600, 400)
        ->storeAs('1', 'evidence.jpg', 'sitrep_photos');

    return Attachment::create([
        'sitrep_id' => Sitrep::sole()->id,
        'file_path' => $path,
        'file_type' => 'image/jpeg',
        'uploaded_by' => $owner->id,
        'uploaded_at' => now(),
    ]);
}

it('lets the author view their own photo', function () {
    $owner = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');
    $attachment = sitrepWithPhoto($owner);

    $this->actingAs($owner)
        ->get(route('attachments.show', $attachment))
        ->assertOk();
});

it('forbids another reporting user from viewing someone elses photo', function () {
    $owner = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');
    $attachment = sitrepWithPhoto($owner);

    $intruder = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');

    $this->actingAs($intruder)
        ->get(route('attachments.show', $attachment))
        ->assertForbidden();
});

it('lets command center view a units photo', function () {
    $owner = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');
    $attachment = sitrepWithPhoto($owner);

    $reviewer = User::factory()->create()->assignRole('command-center');

    $this->actingAs($reviewer)
        ->get(route('attachments.show', $attachment))
        ->assertOk();
});
