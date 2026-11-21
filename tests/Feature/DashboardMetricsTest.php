<?php

use App\Models\ReportType;
use App\Models\Sitrep;
use App\Models\Status;
use App\Models\Unit;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Database\Seeders\ReportTypeSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, StatusSeeder::class, ReportTypeSeeder::class, UnitSeeder::class]);
});

function metricsSitrep(User $creator, string $status, string $type, ?int $unitId = null, ?\Carbon\CarbonInterface $submittedAt = null): Sitrep
{
    static $seq = 900;

    return Sitrep::create([
        'reference_number' => 'SITREP-M-'.$seq++,
        'unit_id' => $unitId ?? $creator->unit_id,
        'report_type_id' => ReportType::where('name', $type)->first()->id,
        'current_status_id' => Status::where('name', $status)->first()->id,
        'created_by' => $creator->id,
        'dynamic_fields' => [],
        'submitted_at' => $status === 'Draft' ? null : ($submittedAt ?? now()),
    ]);
}

it('counts SITREPs by status, type and unit with real totals', function () {
    $unitA = Unit::first();
    $unitB = Unit::skip(1)->first();
    $officer = User::factory()->create(['unit_id' => $unitA->id])->assignRole('reporting-personnel');

    metricsSitrep($officer, 'Submitted', 'Checkpoint');
    metricsSitrep($officer, 'Submitted', 'Checkpoint');
    metricsSitrep($officer, 'Validated', 'Police Visibility', $unitB->id);
    metricsSitrep($officer, 'Draft', 'Checkpoint');

    $m = app(DashboardMetricsService::class);

    expect($m->countsByStatus()->all())->toBe(['Submitted' => 2, 'Draft' => 1, 'Validated' => 1])
        ->and($m->countsByType()->all())->toBe(['Checkpoint' => 3, 'Police Visibility' => 1])
        ->and($m->countsByUnit()->all())->toBe([$unitA->name => 3, $unitB->name => 1])
        ->and($m->countsByUnit(limit: 1)->all())->toBe([$unitA->name => 3]);
});

it('respects a scoped base query', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');
    $other = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');

    metricsSitrep($officer, 'Submitted', 'Checkpoint');
    metricsSitrep($other, 'Submitted', 'Checkpoint');
    metricsSitrep($other, 'Validated', 'Checkpoint');

    $m = app(DashboardMetricsService::class);

    expect($m->countsByStatus($officer->sitreps())->all())->toBe(['Submitted' => 1]);
});

it('builds a zero-filled submission trend for the requested range', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');

    metricsSitrep($officer, 'Submitted', 'Checkpoint', submittedAt: now());
    metricsSitrep($officer, 'Submitted', 'Checkpoint', submittedAt: now()->subDays(2));
    metricsSitrep($officer, 'Submitted', 'Checkpoint', submittedAt: now()->subDays(20)); // outside 7 days
    metricsSitrep($officer, 'Draft', 'Checkpoint');                                    // never counted

    $m = app(DashboardMetricsService::class);

    $week = $m->submissionTrend(days: 7);
    expect($week['labels'])->toHaveCount(7)
        ->and($week['data'])->toBe([0, 0, 0, 0, 1, 0, 1]);

    $month = $m->submissionTrend(days: 30);
    expect($month['data'])->toHaveCount(30)
        ->and(array_sum($month['data']))->toBe(3);

    // Unsupported ranges fall back to 7 days
    expect($m->submissionTrend(days: 12)['data'])->toHaveCount(7);
});

it('shows the admin dashboard with real totals and forbids other roles', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');
    metricsSitrep($officer, 'Submitted', 'Checkpoint');
    metricsSitrep($officer, 'Validated', 'Checkpoint');

    $admin = User::factory()->create()->assignRole('administrator');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Total SITREPs')
        ->assertViewHas('sitrepCount', 2)
        ->assertViewHas('byType', fn ($c) => $c->all() === ['Checkpoint' => 2]);

    $this->actingAs($officer)->get(route('admin.dashboard'))->assertForbidden();
});

it('shows friendly empty states instead of blank charts when there is no data', function () {
    $admin = User::factory()->create()->assignRole('administrator');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('No data for this period yet.')
        ->assertDontSee('id="trendChart"', false);
});

it('renders the admin activity feed from real audit log entries', function () {
    // Regression: AuditLog has $timestamps = false, so created_at is a string;
    // the feed must still render it as a relative time.
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');
    $sitrep = metricsSitrep($officer, 'Submitted', 'Checkpoint');
    \App\Models\AuditLog::create([
        'user_id' => $officer->id, 'sitrep_id' => $sitrep->id, 'action' => 'status_changed',
        'previous_status' => 'Draft', 'new_status' => 'Submitted', 'remarks' => null,
    ]);

    $this->actingAs(User::factory()->create()->assignRole('administrator'))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee($sitrep->reference_number)
        ->assertSee('ago');
});

it('counts review decisions per period from the audit log', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');
    $a = metricsSitrep($officer, 'Validated', 'Checkpoint');
    $b = metricsSitrep($officer, 'Submitted', 'Checkpoint'); // returned earlier, then resubmitted
    foreach ([[$a, 'Validated'], [$b, 'Returned for Correction']] as [$s, $to]) {
        \App\Models\AuditLog::create(['user_id' => $officer->id, 'sitrep_id' => $s->id, 'action' => 'status_changed', 'previous_status' => 'Under Review', 'new_status' => $to]);
    }

    $m = app(DashboardMetricsService::class);
    $p = $m->period(7);

    expect($m->transitionsBetween('Validated', $p['from'], $p['to']))->toBe(1)
        ->and($m->transitionsBetween('Returned for Correction', $p['from'], $p['to']))->toBe(1)
        ->and($m->validationRate($p['from'], $p['to']))->toBe(50.0)
        ->and($m->delta(15, 10))->toBe(50.0)
        ->and($m->delta(5, 0))->toBeNull();
});

it('accepts the range toggle on every dashboard and ignores invalid ranges', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');
    $cmd = User::factory()->create()->assignRole('command-center');
    $admin = User::factory()->create()->assignRole('administrator');
    metricsSitrep($officer, 'Submitted', 'Checkpoint');

    foreach ([[$admin, 'admin.dashboard'], [$cmd, 'command.dashboard'], [$officer, 'reporting.dashboard']] as [$u, $route]) {
        $this->actingAs($u)->get(route($route, ['range' => 30]))->assertOk()->assertViewHas('days', 30);
        $this->actingAs($u)->get(route($route, ['range' => 999]))->assertOk()->assertViewHas('days', 7);
    }
});
