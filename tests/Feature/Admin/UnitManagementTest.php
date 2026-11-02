<?php

use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed([RoleSeeder::class, UnitSeeder::class]));

function sysAdmin(): User
{
    return User::factory()->create()->assignRole('administrator');
}

it('lets admins create a unit', function () {
    $this->actingAs(sysAdmin())->post(route('admin.units.store'), [
        'name' => 'Test Municipal Police Station',
        'code' => 'TEST-MPS',
    ])->assertRedirect(route('admin.units.index'));

    expect(Unit::where('code', 'TEST-MPS')->exists())->toBeTrue();
});

it('rejects duplicate codes and lowercase codes', function () {
    $existing = Unit::first();

    $this->actingAs(sysAdmin())->post(route('admin.units.store'), [
        'name' => 'Duplicate',
        'code' => $existing->code,
    ])->assertSessionHasErrors(['code']);

    $this->actingAs(sysAdmin())->post(route('admin.units.store'), [
        'name' => 'Lowercase',
        'code' => 'basud-mps',
    ])->assertSessionHasErrors(['code']);
});

it('lets admins rename a unit', function () {
    $unit = Unit::first();

    $this->actingAs(sysAdmin())->put(route('admin.units.update', $unit), [
        'name' => 'Renamed Station',
        'code' => $unit->code,
    ])->assertRedirect(route('admin.units.index'));

    expect($unit->refresh()->name)->toBe('Renamed Station');
});

it('refuses to delete a unit that has users', function () {
    $unit = Unit::first();
    User::factory()->create(['unit_id' => $unit->id]);

    $this->actingAs(sysAdmin())->delete(route('admin.units.destroy', $unit))->assertStatus(422);
});

it('deletes an empty unit', function () {
    $unit = Unit::create(['name' => 'Empty Station', 'code' => 'EMPTY-1']);

    $this->actingAs(sysAdmin())->delete(route('admin.units.destroy', $unit))
        ->assertRedirect(route('admin.units.index'));

    expect(Unit::where('code', 'EMPTY-1')->exists())->toBeFalse();
});

it('shows the units list with usage counts', function () {
    $unit = Unit::first();
    User::factory()->create(['unit_id' => $unit->id]);

    $this->actingAs(sysAdmin())
        ->get(route('admin.units.index'))
        ->assertOk()
        ->assertSee($unit->name);
});

it('forbids non-admins from units management', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');

    $this->actingAs($officer)->get(route('admin.units.index'))->assertForbidden();
});
