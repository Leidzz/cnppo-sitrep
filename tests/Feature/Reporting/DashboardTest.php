<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'reporting-personnel']);
    Role::create(['name' => 'command-center']);
    Role::create(['name' => 'administrator']);
});

it('redirects guests to login', function () {
    $this->get(route('reporting.dashboard'))
        ->assertRedirect(route('login'));
});

it('forbids users without the reporting-personnel role', function () {
    $user = User::factory()->create();
    $user->assignRole('command-center');

    $this->actingAs($user)
        ->get(route('reporting.dashboard'))
        ->assertForbidden();
});

it('forbids a user with no roles at all', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('reporting.dashboard'))
        ->assertForbidden();
});

it('shows the welcome message and create button to reporting personnel', function () {
    $user = User::factory()->create(['name' => 'PO1 Santos']);
    $user->assignRole('reporting-personnel');

    $this->actingAs($user)
        ->get(route('reporting.dashboard'))
        ->assertOk()
        ->assertSee('Welcome, PO1 Santos')
        ->assertSee('Create SITREP');
});

it('redirects reporting personnel from /dashboard to their dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('reporting-personnel');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('reporting.dashboard'));
});
