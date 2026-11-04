<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Click;
use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Click>
 */
class ClickFactory extends Factory
{
    public function definition(): array
    {
        return [
            'link_id' => Link::factory(),
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => fake()->userAgent(),
            'referrer' => fake()->boolean(60) ? fake()->url() : null,
            'country' => fake()->boolean(80) ? fake()->countryCode() : null,
            'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge', 'Other']),
            'device' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'clicked_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }
}
