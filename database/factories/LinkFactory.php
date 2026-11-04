<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Link>
 */
class LinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_url' => fake()->url(),
            'code' => Str::random(7),
            'is_active' => true,
            'expires_at' => null,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function expiresIn(int $days): static
    {
        return $this->state(fn () => ['expires_at' => now()->addDays($days)]);
    }
}
