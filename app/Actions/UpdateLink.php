<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Link;
use App\Services\LinkCache;

class UpdateLink
{
    public function __construct(private LinkCache $cache) {}

    /**
     * @param  array{original_url?: string, code?: string, expires_at?: ?string, is_active?: bool}  $attributes
     */
    public function handle(Link $link, array $attributes): Link
    {
        $previousCode = $link->code;

        $link->fill($attributes)->save();

        // Drop the old key so the next redirect reloads from the database.
        $this->cache->forget($previousCode);

        // If the alias changed, also drop the new key in case it held a miss
        // or another stale entry from an earlier conflict during testing.
        if ($link->code !== $previousCode) {
            $this->cache->forget($link->code);
        }

        return $link;
    }
}
