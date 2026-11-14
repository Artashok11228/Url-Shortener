<?php

declare(strict_types=1);

use App\Models\Link;
use App\Models\User;
use App\Services\LinkCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('still redirects from a warm cache', function () {
    Link::factory()->create([
        'code' => 'warm',
        'original_url' => 'https://example.com/warm',
    ]);

    app(LinkCache::class)->remember('warm');

    $this->get('/warm')
        ->assertRedirect('https://example.com/warm')
        ->assertStatus(302);
});

it('does not serve a stale url after the link is updated', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->for($owner)->create([
        'code' => 'edit-me',
        'original_url' => 'https://example.com/before',
    ]);

    $this->get('/edit-me')->assertRedirect('https://example.com/before');

    Sanctum::actingAs($owner);

    $this->putJson('/api/links/'.$link->id, [
        'original_url' => 'https://example.com/after',
    ])->assertOk();

    $this->get('/edit-me')->assertRedirect('https://example.com/after');
});

it('returns 404 after a cached link is deleted', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->for($owner)->create([
        'code' => 'gone-cache',
        'original_url' => 'https://example.com',
    ]);

    $this->get('/gone-cache')->assertRedirect('https://example.com');

    Sanctum::actingAs($owner);
    $this->deleteJson('/api/links/'.$link->id)->assertNoContent();

    $this->get('/gone-cache')->assertNotFound();
});

it('stops using the old alias after a code change', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->for($owner)->create([
        'code' => 'old-alias',
        'original_url' => 'https://example.com',
    ]);

    $this->get('/old-alias')->assertRedirect('https://example.com');

    Sanctum::actingAs($owner);
    $this->putJson('/api/links/'.$link->id, ['alias' => 'new-alias'])->assertOk();

    $this->get('/old-alias')->assertNotFound();
    $this->get('/new-alias')->assertRedirect('https://example.com');
});

it('returns 410 for an expired link even when the cache is warm', function () {
    Carbon::setTestNow('2026-06-01 11:00:00');

    Link::factory()->create([
        'code' => 'will-expire',
        'original_url' => 'https://example.com',
        'expires_at' => Carbon::parse('2026-06-01 12:00:00'),
    ]);

    $this->get('/will-expire')->assertRedirect('https://example.com');

    Carbon::setTestNow('2026-06-01 12:00:00');

    $this->get('/will-expire')->assertGone();

    Carbon::setTestNow();
});

it('returns 404 when a cached link is deactivated', function () {
    $owner = User::factory()->create();
    $link = Link::factory()->for($owner)->create([
        'code' => 'pause-me',
        'original_url' => 'https://example.com',
    ]);

    $this->get('/pause-me')->assertRedirect('https://example.com');

    Sanctum::actingAs($owner);
    $this->putJson('/api/links/'.$link->id, ['is_active' => false])->assertOk();

    $this->get('/pause-me')->assertNotFound();
});
