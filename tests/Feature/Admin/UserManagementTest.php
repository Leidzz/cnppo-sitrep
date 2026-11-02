<?php

use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed([RoleSeeder::class, UnitSeeder::class]));

function theAdmin(): User
{
    return User::factory()->create()->assignRole('administrator');
}

it('lets admins create a user with role and unit', function () {
    $unit = Unit::first();

    $this->actingAs(theAdmin())->post(route('admin.users.store'), [
        'name' => 'PO1 New Officer',
        'email' => 'newofficer@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'unit_id' => $unit->id,
        'role' => 'reporting-personnel',
    ])->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'newofficer@example.com')->sole();
    expect($user->hasRole('reporting-personnel'))->toBeTrue();
    expect($user->unit_id)->toBe($unit->id);
});

it('blocks deactivated users on any authenticated page', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id, 'is_active' => false])
        ->assignRole('reporting-personnel');

    $this->actingAs($officer)->get(route('reporting.dashboard'))->assertForbidden();
});

it('lets admins deactivate and reactivate a user', function () {
    $target = User::factory()->create(['unit_id' => Unit::first()->id, 'is_active' => true])
        ->assignRole('reporting-personnel');

    $this->actingAs(theAdmin())->post(route('admin.users.toggleActive', $target));
    expect($target->refresh()->is_active)->toBeFalse();

    $this->actingAs(theAdmin())->post(route('admin.users.toggleActive', $target));
    expect($target->refresh()->is_active)->toBeTrue();
});

it('prevents admins from deactivating themselves', function () {
    $admin = theAdmin();

    $this->actingAs($admin)->post(route('admin.users.toggleActive', $admin))->assertStatus(422);
});

it('forbids non-admins from user management', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');

    $this->actingAs($officer)->get(route('admin.users.index'))->assertForbidden();
});

it('DEBUG factory is_active value', function () {
    $u = User::factory()->create();
    dump($u->getAttributes()['is_active'] ?? 'KEY MISSING');
    $this->assertTrue(true);
});
