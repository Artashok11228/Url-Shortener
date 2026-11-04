<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Link;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_hides_disabled_links(): void
    {
        Link::factory()->create();
        Link::factory()->disabled()->create();

        $this->assertSame(1, Link::query()->active()->count());
    }

    public function test_not_expired_scope_keeps_links_without_expiry_and_future_expiry(): void
    {
        Link::factory()->create();
        Link::factory()->expiresIn(3)->create();
        Link::factory()->expired()->create();

        $this->assertSame(2, Link::query()->notExpired()->count());
    }

    public function test_is_expired_reflects_expiry_date(): void
    {
        $this->assertFalse(Link::factory()->make()->isExpired());
        $this->assertFalse(Link::factory()->expiresIn(1)->make()->isExpired());
        $this->assertTrue(Link::factory()->expired()->make()->isExpired());
    }

    public function test_deleting_a_user_removes_their_links_and_clicks(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->for($user)->create();
        Click::factory()->count(3)->for($link)->create();

        $user->delete();

        $this->assertDatabaseCount('links', 0);
        $this->assertDatabaseCount('clicks', 0);
    }

    public function test_seeder_creates_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(User::query()->where('email', 'admin@example.com')->first()->isAdmin());
        $this->assertNotNull(Link::query()->where('code', 'laravel')->first());
        $this->assertGreaterThan(100, Click::query()->count());
    }
}
