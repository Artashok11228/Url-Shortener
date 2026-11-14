<?php

declare(strict_types=1);

use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a user and can attach to links', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'work']);
    $link = Link::factory()->for($user)->create();

    $link->tags()->attach($tag);

    expect($tag->user->is($user))->toBeTrue()
        ->and($link->fresh()->tags)->toHaveCount(1)
        ->and($link->tags->first()->name)->toBe('work')
        ->and($user->tags)->toHaveCount(1);
});

it('enforces unique tag names per user', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['name' => 'promo']);

    expect(fn () => Tag::factory()->for($user)->create(['name' => 'promo']))
        ->toThrow(UniqueConstraintViolationException::class);
});
