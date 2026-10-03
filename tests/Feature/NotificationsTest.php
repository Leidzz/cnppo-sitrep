<?php

use App\Models\Notification;
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

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, StatusSeeder::class, ReportTypeSeeder::class, UnitSeeder::class]);
});

it('notifies command center when a sitrep is submitted', function () {
    $reviewer = User::factory()->create()->assignRole('command-center');
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');

    $this->actingAs($officer)->post(route('reporting.sitreps.store'), [
        'report_type_id' => ReportType::where('name', 'Checkpoint')->first()->id,
        'action' => 'submit',
        'dynamic_fields' => ['what' => 'x', 'where' => 'y', 'when' => 'z', 'how' => 'w'],
    ]);

    expect(Notification::where('user_id', $reviewer->id)->where('type', 'new_sitrep')->count())->toBe(1);
    expect(Notification::where('user_id', $officer->id)->count())->toBe(0); // author not pinged for own submit
});

it('notifies the author when validated and includes reviewer remarks when returned', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');
    $reviewer = User::factory()->create()->assignRole('command-center');

    static $seq = 700;
    $sitrep = Sitrep::create([
        'reference_number' => 'SITREP-N-'.$seq++,
        'unit_id' => $officer->unit_id,
        'report_type_id' => ReportType::where('name', 'Checkpoint')->first()->id,
        'current_status_id' => Status::where('name', 'Submitted')->first()->id,
        'created_by' => $officer->id,
        'dynamic_fields' => [],
        'submitted_at' => now(),
    ]);

    $this->actingAs($reviewer)->post(route('command.sitreps.decide', $sitrep), [
        'decision' => 'returned',
        'remarks' => 'Fix the narrative.',
    ]);

    $note = Notification::where('user_id', $officer->id)->sole();
    expect($note->type)->toBe('sitrep_returned');
    expect($note->message)->toContain('Fix the narrative.');
    expect((bool) $note->is_read)->toBeFalse();
});

it('notifications page lists items and marks all read', function () {
    $user = User::factory()->create()->assignRole('reporting-personnel');

    Notification::create(['user_id' => $user->id, 'type' => 't', 'message' => 'Hello']);
    Notification::create(['user_id' => $user->id, 'type' => 't', 'message' => 'World']);

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Hello')
        ->assertSee('unread');;

    $this->actingAs($user)->post(route('notifications.readAll'))
        ->assertRedirect(route('notifications.index'));

    expect(Notification::where('user_id', $user->id)->where('is_read', false)->count())->toBe(0);
});
