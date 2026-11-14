<?php

declare(strict_types=1);

use App\Models\Click;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Count SQL statements executed during the callback.
 * Setup (factories) must happen before calling this so they are not counted.
 */
function countQueries(callable $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

function seedDashboardLinks(User $user, int $linkCount): void
{
    for ($i = 0; $i < $linkCount; $i++) {
        $link = Link::factory()->for($user)->create();
        Click::factory()->count(2)->for($link)->create();
        $tag = Tag::factory()->for($user)->create(['name' => "tag-{$i}"]);
        $link->tags()->attach($tag);
    }
}

it('keeps dashboard query count stable as link count grows', function () {
    $smallUser = User::factory()->create();
    seedDashboardLinks($smallUser, 5);

    $smallQueries = countQueries(function () use ($smallUser): void {
        $this->actingAs($smallUser)->get('/dashboard')->assertOk();
    });

    $largeUser = User::factory()->create();
    seedDashboardLinks($largeUser, 20);

    $largeQueries = countQueries(function () use ($largeUser): void {
        $this->actingAs($largeUser)->get('/dashboard')->assertOk();
    });

    // Eager loading: extra rows must not add one query per link.
    expect($largeQueries - $smallQueries)->toBeLessThanOrEqual(2)
        ->and($largeQueries)->toBeLessThan(20);
});

it('documents that lazy loading grows linearly with link count', function () {
    $user = User::factory()->create();
    seedDashboardLinks($user, 5);

    $lazyFive = countQueries(function () use ($user): void {
        $links = $user->links()->latest()->get();

        foreach ($links as $link) {
            $link->tags->count();
            $link->clicks()->count();
        }
    });

    $userTwenty = User::factory()->create();
    seedDashboardLinks($userTwenty, 20);

    $lazyTwenty = countQueries(function () use ($userTwenty): void {
        $links = $userTwenty->links()->latest()->get();

        foreach ($links as $link) {
            $link->tags->count();
            $link->clicks()->count();
        }
    });

    // 1 list query + N tags + N click counts → roughly 1 + 2N
    expect($lazyTwenty)->toBeGreaterThan($lazyFive + 20)
        ->and($lazyFive)->toBeGreaterThanOrEqual(11);
});
