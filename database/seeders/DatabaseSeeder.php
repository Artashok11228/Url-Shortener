<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Click;
use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $demo = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        Link::factory()->for($demo)->create([
            'original_url' => 'https://laravel.com/docs',
            'code' => 'laravel',
        ])->clicks()->saveMany(Click::factory()->count(120)->make());

        Link::factory()->for($demo)->expired()->create([
            'original_url' => 'https://example.com/old-campaign',
        ]);

        Link::factory()->for($demo)->disabled()->create([
            'original_url' => 'https://example.com/paused',
        ]);

        User::factory()->count(3)->create()->each(function (User $user) {
            Link::factory()->count(4)->for($user)->create()->each(function (Link $link) {
                Click::factory()->count(random_int(5, 40))->for($link)->create();
            });
        });
    }
}
