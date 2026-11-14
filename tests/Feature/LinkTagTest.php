<?php

declare(strict_types=1);

use App\Actions\SyncLinkTags;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('syncs tag names onto a link for the link owner', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->for($owner)->create();

    $synced = app(SyncLinkTags::class)->handle($link, [' work ', 'promo', 'work', '']);

    expect($synced->tags->pluck('name')->sort()->values()->all())->toBe(['promo', 'work'])
        ->and($owner->fresh()->tags)->toHaveCount(2);
});

it('does not attach another users existing tag when syncing by name', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $link = Link::factory()->for($owner)->create();
    $foreign = Tag::factory()->for($other)->create(['name' => 'shared']);

    app(SyncLinkTags::class)->handle($link, ['shared']);

    $link->refresh();

    expect($link->tags)->toHaveCount(1)
        ->and($link->tags->first()->user_id)->toBe($owner->id)
        ->and($link->tags->first()->is($foreign))->toBeFalse()
        ->and($other->fresh()->tags)->toHaveCount(1);
});

it('replaces previous tags on sync', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->for($owner)->create();

    app(SyncLinkTags::class)->handle($link, ['a', 'b']);
    app(SyncLinkTags::class)->handle($link, ['c']);

    expect($link->fresh()->tags->pluck('name')->all())->toBe(['c']);
});
