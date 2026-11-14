<?php

declare(strict_types=1);

use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('redirects an active link to the original url', function () {
    $link = Link::factory()->create([
        'code' => 'go-here',
        'original_url' => 'https://example.com/landing',
    ]);

    $this->get('/go-here')
        ->assertRedirect('https://example.com/landing')
        ->assertStatus(302);

    expect($link->fresh()->is_active)->toBeTrue();
});

it('returns 404 when the code does not exist', function () {
    $this->get('/missing-code')->assertNotFound();
});

it('returns 404 when the link was soft deleted', function () {
    $link = Link::factory()->create(['code' => 'deleted']);
    $link->delete();

    $this->get('/deleted')->assertNotFound();
});

it('returns 404 when the link is inactive', function () {
    Link::factory()->disabled()->create(['code' => 'paused']);

    $this->get('/paused')->assertNotFound();
});

it('returns 410 when the link has expired', function () {
    Link::factory()->expired()->create(['code' => 'old-link']);

    $this->get('/old-link')->assertGone();
});

it('treats the exact expiry moment as expired', function () {
    Carbon::setTestNow('2026-06-01 12:00:00');

    Link::factory()->create([
        'code' => 'boundary',
        'expires_at' => Carbon::parse('2026-06-01 12:00:00'),
    ]);

    $this->get('/boundary')->assertGone();

    Carbon::setTestNow();
});

it('still redirects one second before expiry', function () {
    Carbon::setTestNow('2026-06-01 11:59:59');

    Link::factory()->create([
        'code' => 'almost',
        'original_url' => 'https://example.org/soon',
        'expires_at' => Carbon::parse('2026-06-01 12:00:00'),
    ]);

    $this->get('/almost')
        ->assertRedirect('https://example.org/soon')
        ->assertStatus(302);

    Carbon::setTestNow();
});

it('records a click during a successful redirect', function () {
    Link::factory()->create([
        'code' => 'plain',
        'original_url' => 'https://example.com',
    ]);

    $this->get('/plain')->assertRedirect('https://example.com');

    $this->assertDatabaseCount('clicks', 1);
});
