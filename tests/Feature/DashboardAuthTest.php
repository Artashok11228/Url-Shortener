<?php

declare(strict_types=1);

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from the dashboard to login', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

it('logs a user in with a session and shows the dashboard', function () {
    $user = User::factory()->create([
        'email' => 'demo@example.com',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'demo@example.com',
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Your links');
});

it('rejects invalid web login credentials', function () {
    User::factory()->create([
        'email' => 'demo@example.com',
        'password' => 'password',
    ]);

    $this->from('/login')->post('/login', [
        'email' => 'demo@example.com',
        'password' => 'wrong-password',
    ])->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('does not show another users links on the dashboard', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Link::factory()->for($user)->create(['code' => 'mine-01', 'original_url' => 'https://example.com/mine']);
    Link::factory()->for($other)->create(['code' => 'theirs1', 'original_url' => 'https://example.com/theirs']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('mine-01')
        ->assertDontSee('theirs1')
        ->assertDontSee('https://example.com/theirs');
});

it('lets an admin see everyones links', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();

    Link::factory()->for($owner)->create(['code' => 'owned-1']);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('owned-1');
});

it('logs the user out and clears the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});
