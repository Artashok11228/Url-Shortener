<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

it('connects to the application database', function () {
    expect(DB::connection()->getPdo())->not->toBeNull();
    expect(DB::select('select 1 as ok')[0]->ok)->toBe(1);
});

it('keeps redis out of cache and queue in phase one', function () {
    expect(config('cache.default'))->not->toBe('redis');
    expect(config('queue.default'))->not->toBe('redis');

    $envExample = file_get_contents(base_path('.env.example'));

    expect($envExample)
        ->toContain('CACHE_STORE=file')
        ->toContain('QUEUE_CONNECTION=sync');
});

it('can talk to redis when the service is available', function () {
    try {
        $pong = Redis::connection()->ping();
    } catch (Throwable $exception) {
        test()->markTestSkipped('Redis is not running: '.$exception->getMessage());
    }

    expect($pong === true || $pong === 'PONG' || $pong === '+PONG')->toBeTrue();
});
