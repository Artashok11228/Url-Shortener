<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\RecordClick;
use App\Models\Link;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Persists one click row in the background.
 *
 * Delivery is at-least-once: if the worker crashes after INSERT but before
 * acknowledging the job, a retry can create a duplicate row. That is accepted
 * here so redirects never wait on DB writes; exact counts belong to later analytics.
 */
class RecordClickJob implements ShouldQueue
{
    use Queueable;

    /** @var int Maximum attempts before the job is marked failed. */
    public int $tries = 3;

    /**
     * Seconds to wait before each retry (transient DB blips, etc.).
     *
     * @var list<int>
     */
    public array $backoff = [1, 5, 15];

    public function __construct(
        public int $linkId,
        public string $ip,
        public ?string $userAgent,
        public ?string $referrer,
        public string $clickedAt,
    ) {}

    public function handle(RecordClick $recordClick): void
    {
        $link = Link::query()->find($this->linkId);

        // Soft-deleted or hard-deleted links: drop quietly. Cascade delete already
        // removes clicks; inventing a row for a gone link helps nobody.
        if ($link === null) {
            return;
        }

        $recordClick->handle(
            $link,
            $this->ip,
            $this->userAgent,
            $this->referrer,
            country: null,
            clickedAt: Carbon::parse($this->clickedAt),
        );
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Click recording failed after all retries', [
            'link_id' => $this->linkId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
