<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Link;
use App\Services\LinkCache;

class ResolveLink
{
    public function __construct(private LinkCache $cache) {}

    /**
     * Load a link for redirect, preferring Redis/array cache over a DB hit.
     *
     * Expiry and active checks stay in the controller so a warm cache can
     * still return 410/404 when the clock or status makes the link unusable.
     */
    public function handle(string $code): ?Link
    {
        return $this->cache->remember($code);
    }
}
