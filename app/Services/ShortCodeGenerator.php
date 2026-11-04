<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Link;
use RuntimeException;

class ShortCodeGenerator
{
    private const string ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function generate(): string
    {
        $attempts = config('shortener.code_max_attempts');

        for ($i = 0; $i < $attempts; $i++) {
            $code = $this->randomCode();

            if (! $this->isTaken($code)) {
                return $code;
            }
        }

        throw new RuntimeException("Could not find a free short code after {$attempts} attempts.");
    }

    private function randomCode(): string
    {
        $length = config('shortener.code_length');
        $max = strlen(self::ALPHABET) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return $code;
    }

    private function isTaken(string $code): bool
    {
        // Soft-deleted links keep their code, so they count as taken too.
        return Link::withTrashed()->where('code', $code)->exists()
            || in_array(strtolower($code), config('shortener.reserved_aliases'), true);
    }
}
