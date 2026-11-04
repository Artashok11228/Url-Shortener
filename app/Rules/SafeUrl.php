<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts only public http(s) URLs. Rejects anything that could point a
 * visitor at localhost, a private network or back at this service.
 */
class SafeUrl implements ValidationRule
{
    private const array ALLOWED_SCHEMES = ['http', 'https'];

    private const array LOCAL_SUFFIXES = ['.localhost', '.local', '.internal', '.test'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || strlen($value) > config('shortener.max_url_length')) {
            $fail('The :attribute may not be longer than '.config('shortener.max_url_length').' characters.');

            return;
        }

        $host = $this->publicHostOf($value);

        if ($host === null) {
            $fail('The :attribute must be a valid http or https URL.');

            return;
        }

        if ($this->isOwnHost($host)) {
            $fail('The :attribute may not point back to this service.');

            return;
        }

        if ($this->isLocalOrPrivate($host)) {
            $fail('The :attribute must point to a public host.');
        }
    }

    private function publicHostOf(string $url): ?string
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(trim($parts['host'] ?? '', '[]'));

        if (! in_array($scheme, self::ALLOWED_SCHEMES, true) || $host === '') {
            return null;
        }

        return $host;
    }

    private function isOwnHost(string $host): bool
    {
        return $host === strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
    }

    private function isLocalOrPrivate(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        }

        if ($host === 'localhost' || ! str_contains($host, '.')) {
            return true;
        }

        // "127.1" or "2130706433" are not valid IPs for PHP but browsers still resolve them
        // to 127.0.0.1. A real public domain never ends in a numeric label.
        if (ctype_digit(substr(strrchr($host, '.'), 1))) {
            return true;
        }

        foreach (self::LOCAL_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
