<?php

declare(strict_types=1);

use App\Actions\RecordClick;
use App\Events\LinkClicked;
use App\Jobs\RecordClickJob;
use App\Models\Click;
use App\Models\Link;
use App\Services\LinkCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches LinkClicked when a redirect succeeds', function () {
    Event::fake([LinkClicked::class]);

    $link = Link::factory()->create([
        'code' => 'click-me',
        'original_url' => 'https://example.com/target',
    ]);

    $this->get('/click-me')
        ->assertRedirect('https://example.com/target');

    Event::assertDispatched(LinkClicked::class, function (LinkClicked $event) use ($link) {
        return $event->linkId === $link->id;
    });
});

it('does not dispatch LinkClicked for missing or expired links', function () {
    Event::fake([LinkClicked::class]);

    Link::factory()->expired()->create(['code' => 'gone']);

    $this->get('/missing')->assertNotFound();
    $this->get('/gone')->assertGone();

    Event::assertNotDispatched(LinkClicked::class);
});

it('queues RecordClickJob when LinkClicked is handled', function () {
    Queue::fake([RecordClickJob::class]);

    $link = Link::factory()->create([
        'code' => 'queue-me',
        'original_url' => 'https://example.com',
    ]);

    $this->get('/queue-me')->assertRedirect('https://example.com');

    Queue::assertPushed(RecordClickJob::class, function (RecordClickJob $job) use ($link) {
        return $job->linkId === $link->id;
    });
});

it('stores a click row when the job runs', function () {
    $link = Link::factory()->create([
        'code' => 'store-me',
        'original_url' => 'https://example.com/store',
    ]);

    // phpunit.xml uses QUEUE_CONNECTION=sync, so the job runs inside the request.
    $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 Firefox/120.0',
        'Referer' => 'https://news.example/article',
    ])->get('/store-me')
        ->assertRedirect('https://example.com/store');

    $this->assertDatabaseCount('clicks', 1);

    $click = Click::query()->first();

    expect($click->link_id)->toBe($link->id)
        ->and($click->ip_hash)->not->toBeEmpty()
        ->and($click->referrer)->toBe('https://news.example/article')
        ->and($click->user_agent)->toContain('Firefox');
});

it('still records a click when the redirect is served from cache', function () {
    $link = Link::factory()->create([
        'code' => 'warm',
        'original_url' => 'https://example.com/warm',
    ]);

    app(LinkCache::class)->remember('warm');

    $this->get('/warm')->assertRedirect('https://example.com/warm');

    $this->assertDatabaseCount('clicks', 1);
    expect(Click::query()->value('link_id'))->toBe($link->id);
});

it('skips insert when the link no longer exists', function () {
    $job = new RecordClickJob(
        linkId: 999_999,
        ip: '127.0.0.1',
        userAgent: null,
        referrer: null,
        clickedAt: now()->toIso8601String(),
    );

    $job->handle(app(RecordClick::class));

    $this->assertDatabaseCount('clicks', 0);
});

it('logs a warning when click recording fails permanently', function () {
    Log::spy();

    $job = new RecordClickJob(
        linkId: 1,
        ip: '127.0.0.1',
        userAgent: null,
        referrer: null,
        clickedAt: now()->toIso8601String(),
    );

    $job->failed(new RuntimeException('disk full'));

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return $message === 'Click recording failed after all retries'
                && $context['link_id'] === 1
                && $context['exception'] === 'disk full';
        });
});
