<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Link;
use Illuminate\Support\Facades\Cache;

/**
 * Cache-aside lookup for public redirects.
 *
 * We store a plain array (not the Eloquent model) so Redis/file unserialize
 * never returns __PHP_Incomplete_Class when the class map is not ready yet.
 */
class LinkCache
{
    public function key(string $code): string
    {
        return 'links:code:'.$code;
    }

    public function remember(string $code): ?Link
    {
        /** @var array{id: int, original_url: string, code: string, is_active: bool, expires_at: ?string}|null $payload */
        $payload = Cache::remember(
            $this->key($code),
            (int) config('shortener.cache_ttl'),
            function () use ($code): ?array {
                $link = Link::query()->where('code', $code)->first();

                if ($link === null) {
                    return null;
                }

                return [
                    'id' => $link->id,
                    'original_url' => $link->original_url,
                    'code' => $link->code,
                    'is_active' => $link->is_active,
                    'expires_at' => $link->expires_at?->toIso8601String(),
                ];
            },
        );

        if ($payload === null) {
            return null;
        }

        return $this->rehydrate($payload);
    }

    public function forget(string $code): void
    {
        Cache::forget($this->key($code));
    }

    /**
     * @param  array{id: int, original_url: string, code: string, is_active: bool, expires_at: ?string}  $payload
     */
    private function rehydrate(array $payload): Link
    {
        $link = new Link;
        $link->forceFill([
            'id' => $payload['id'],
            'original_url' => $payload['original_url'],
            'code' => $payload['code'],
            'is_active' => $payload['is_active'],
            'expires_at' => $payload['expires_at'],
        ]);
        $link->exists = true;

        return $link;
    }
}
