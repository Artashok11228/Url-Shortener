<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Click;
use App\Models\Link;
use App\Services\UserAgentParser;
use Carbon\CarbonInterface;

class RecordClick
{
    public function __construct(private UserAgentParser $userAgents) {}

    public function handle(
        Link $link,
        string $ip,
        ?string $userAgent,
        ?string $referrer,
        ?string $country,
        CarbonInterface $clickedAt,
    ): Click {
        return $link->clicks()->create([
            'ip_hash' => $this->hashIp($ip),
            'user_agent' => $userAgent,
            'referrer' => $referrer,
            'country' => $country !== null ? strtoupper(substr($country, 0, 2)) : null,
            'browser' => $this->userAgents->browser($userAgent),
            'device' => $this->userAgents->device($userAgent),
            'clicked_at' => $clickedAt,
        ]);
    }

    private function hashIp(string $ip): string
    {
        // Keyed hash: the same visitor hashes to the same value (so unique counts work),
        // but nobody can brute-force the ~4 billion IPv4 addresses without the app key.
        return hash_hmac('sha256', $ip, config('app.key'));
    }
}
