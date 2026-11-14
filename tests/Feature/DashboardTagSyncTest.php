<?php

declare(strict_types=1);

use App\Models\Click;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows click counts and tags on the dashboard', function () {
    $user = User::factory()->create();
    $link = Link::factory()->for($user)->create(['code' => 'tagged1']);
    Click::factory()->count(3)->for($link)->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'promo']);
    $link->tags()->attach($tag);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('tagged1')
        ->assertSee('3')
        ->assertSee('promo');
});

it('lets the owner sync tags from the dashboard form', function () {
    $user = User::factory()->create();
    $link = Link::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('dashboard.links.tags.update', $link), [
            'tags' => 'work, docs',
        ])
        ->assertRedirect(route('dashboard'));

    expect($link->fresh()->tags->pluck('name')->sort()->values()->all())->toBe(['docs', 'work']);
});

it('forbids another user from editing tags', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $link = Link::factory()->for($owner)->create();

    $this->actingAs($other)
        ->put(route('dashboard.links.tags.update', $link), [
            'tags' => 'stolen',
        ])
        ->assertForbidden();

    expect($link->fresh()->tags)->toHaveCount(0);
});

it('paginates the dashboard list', function () {
    $user = User::factory()->create();
    Link::factory()->count(16)->for($user)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('?page=2', false);
});
