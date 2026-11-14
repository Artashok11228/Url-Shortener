<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LinkClicked;
use App\Jobs\RecordClickJob;

/**
 * Bridges the domain event to the queue.
 *
 * Stays synchronous on purpose: the HTTP request only pays for one Redis
 * LPUSH (or sync run in tests). The slow INSERT happens inside the job.
 */
class DispatchRecordClickJob
{
    public function handle(LinkClicked $event): void
    {
        RecordClickJob::dispatch(
            $event->linkId,
            $event->ip,
            $event->userAgent,
            $event->referrer,
            $event->clickedAt->toIso8601String(),
        );
    }
}
