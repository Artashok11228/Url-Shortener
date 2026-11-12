<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

it('connects to the application database', function () {
    expect(DB::connection()->getPdo())->not->toBeNull();
    expect(DB::select('select 1 as ok')[0]->ok)->toBe(1);
});

it('uses redis for cache and queue in the product defaults', function () {
    // phpunit.xml forces CACHE_STORE=array and QUEUE_CONNECTION=sync for isolated tests.
    // .env.example is the product default shipped for local/Sail.
    $envExample = file_get_contents(base_path('.env.example'));

    expect($envExample)
        ->toContain('CACHE_STORE=redis')
        ->toContain('QUEUE_CONNECTION=redis');
});

it('can talk to redis when the service is available', function () {
    try {
        $pong = Redis::connection()->ping();
    } catch (Throwable $exception) {
        test()->markTestSkipped('Redis is not running: '.$exception->getMessage());
    }

    // Predis 3 returns a Status object; phpredis may return true or "PONG".
    $normalized = is_object($pong) ? (string) $pong : $pong;

    expect($normalized === true || $normalized === 'PONG' || $normalized === '+PONG')->toBeTrue();
});
