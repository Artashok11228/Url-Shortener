<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Browser;
use App\Enums\Device;

/**
 * Good-enough detection for stats. Order matters: Chromium-based browsers
 * all contain "Chrome" and "Safari", so the more specific tokens go first.
 */
class UserAgentParser
{
    public function browser(?string $userAgent): Browser
    {
        return match (true) {
            $userAgent === null => Browser::Other,
            str_contains($userAgent, 'Edg/') => Browser::Edge,
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => Browser::Opera,
            str_contains($userAgent, 'Firefox/') => Browser::Firefox,
            str_contains($userAgent, 'Chrome/') || str_contains($userAgent, 'CriOS/') => Browser::Chrome,
            str_contains($userAgent, 'Safari/') => Browser::Safari,
            default => Browser::Other,
        };
    }

    public function device(?string $userAgent): Device
    {
        return match (true) {
            $userAgent === null => Device::Desktop,
            (bool) preg_match('/bot|crawler|spider|curl|wget|preview/i', $userAgent) => Device::Bot,
            (bool) preg_match('/iPad|Tablet/i', $userAgent) => Device::Tablet,
            // Android tablets identify as Android without the "Mobile" token.
            str_contains($userAgent, 'Android') && ! str_contains($userAgent, 'Mobile') => Device::Tablet,
            (bool) preg_match('/Mobile|iPhone|Android/i', $userAgent) => Device::Mobile,
            default => Device::Desktop,
        };
    }
}
