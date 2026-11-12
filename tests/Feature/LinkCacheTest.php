<?php

declare(strict_types=1);

use App\Models\Link;
use App\Services\LinkCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('remembers a link under a stable code key', function () {
    $link = Link::factory()->create([
        'code' => 'cached1',
        'original_url' => 'https://example.com/cached',
    ]);
    $cache = app(LinkCache::class);

    $resolved = $cache->remember('cached1');

    expect($resolved)->not->toBeNull()
        ->and($resolved->code)->toBe('cached1')
        ->and($resolved->original_url)->toBe('https://example.com/cached')
        ->and(Cache::has($cache->key('cached1')))->toBeTrue();

    // Second call must not need another query; deleting the row proves the hit.
    $link->forceDelete();

    expect($cache->remember('cached1')->original_url)->toBe('https://example.com/cached');
});

it('forgets a cached link so the next remember hits the database', function () {
    $link = Link::factory()->create([
        'code' => 'forget-me',
        'original_url' => 'https://example.com/old',
    ]);
    $cache = app(LinkCache::class);

    $cache->remember('forget-me');
    $cache->forget('forget-me');

    $link->update(['original_url' => 'https://example.com/new']);

    expect($cache->remember('forget-me')->original_url)->toBe('https://example.com/new');
});
