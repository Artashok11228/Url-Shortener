<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Link;
use App\Models\User;
use App\Services\ShortCodeGenerator;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;

class CreateLink
{
    public function __construct(private ShortCodeGenerator $codes) {}

    public function handle(User $owner, string $originalUrl, ?string $alias = null, ?CarbonInterface $expiresAt = null): Link
    {
        if ($alias !== null) {
            return $this->store($owner, $originalUrl, $alias, $expiresAt);
        }

        // Two requests can draw the same random code between the free-check and the
        // insert. The unique index rejects the loser, and drawing again is all it takes.
        try {
            return $this->store($owner, $originalUrl, $this->codes->generate(), $expiresAt);
        } catch (UniqueConstraintViolationException) {
            return $this->store($owner, $originalUrl, $this->codes->generate(), $expiresAt);
        }
    }

    private function store(User $owner, string $originalUrl, string $code, ?CarbonInterface $expiresAt): Link
    {
        return $owner->links()->create([
            'original_url' => $originalUrl,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);
    }
}
