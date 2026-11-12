<?php

declare(strict_types=1);

namespace App\Events;

use Carbon\CarbonInterface;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after a short link successfully resolves to a redirect.
 *
 * Carries only scalars so queued listeners/jobs do not depend on a live
 * Eloquent model (the link may be deleted before the worker runs).
 */
class LinkClicked
{
    use Dispatchable;

    public function __construct(
        public int $linkId,
        public string $ip,
        public ?string $userAgent,
        public ?string $referrer,
        public CarbonInterface $clickedAt,
    ) {}
}
