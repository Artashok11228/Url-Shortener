<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LinkManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_user_can_create_a_link_with_a_generated_code(): void
    {
        $response = $this->postJson('/api/links', ['original_url' => 'https://example.com/page']);

        $response->assertCreated()
            ->assertJsonPath('data.original_url', 'https://example.com/page')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.expires_at', null);

        $code = $response->json('data.code');
        $this->assertSame(7, strlen($code));
        $this->assertSame(url('/'.$code), $response->json('data.short_url'));
        $this->assertDatabaseHas('links', ['code' => $code, 'user_id' => $this->user->id]);
    }

    public function test_user_can_choose_an_alias_and_expiry(): void
    {
        $response = $this->postJson('/api/links', [
            'original_url' => 'https://example.com',
            'alias' => 'my-launch',
            'expires_at' => now()->addWeek()->toIso8601String(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'my-launch')
            ->assertJsonPath('data.is_expired', false);
        $this->assertNotNull($response->json('data.expires_at'));
    }

    public function test_alias_must_be_unique_even_against_deleted_links(): void
    {
        Link::factory()->create(['code' => 'taken']);
        Link::factory()->create(['code' => 'gone'])->delete();

        $this->postJson('/api/links', ['original_url' => 'https://example.com', 'alias' => 'taken'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('alias');

        $this->postJson('/api/links', ['original_url' => 'https://example.com', 'alias' => 'gone'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('alias');
    }

    public function test_reserved_aliases_are_rejected(): void
    {
        foreach (['api', 'Admin', 'login'] as $alias) {
            $this->postJson('/api/links', ['original_url' => 'https://example.com', 'alias' => $alias])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('alias');
        }
    }

    public function test_alias_format_is_restricted(): void
    {
        foreach (['ab', 'has space', 'ünïcode', str_repeat('a', 33)] as $alias) {
            $this->postJson('/api/links', ['original_url' => 'https://example.com', 'alias' => $alias])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('alias');
        }
    }

    public function test_unsafe_urls_are_rejected(): void
    {
        foreach (['not-a-url', 'ftp://example.com', 'http://localhost', 'http://192.168.0.1', 'http://169.254.169.254/'] as $url) {
            $this->postJson('/api/links', ['original_url' => $url])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('original_url');
        }
    }

    public function test_expiry_must_be_in_the_future(): void
    {
        $this->postJson('/api/links', ['original_url' => 'https://example.com', 'expires_at' => now()->subDay()->toIso8601String()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expires_at');
    }

    public function test_user_only_sees_their_own_links(): void
    {
        Link::factory()->count(2)->for($this->user)->create();
        Link::factory()->create();

        $this->getJson('/api/links')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_view_update_and_delete_their_own_link(): void
    {
        $link = Link::factory()->for($this->user)->create();

        $this->getJson("/api/links/{$link->id}")->assertOk()->assertJsonPath('data.id', $link->id);

        $this->patchJson("/api/links/{$link->id}", ['original_url' => 'https://example.org/new', 'alias' => 'renamed', 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.original_url', 'https://example.org/new')
            ->assertJsonPath('data.code', 'renamed')
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/links/{$link->id}")->assertNoContent();
        $this->assertSoftDeleted($link);
        $this->getJson("/api/links/{$link->id}")->assertNotFound();
    }

    public function test_updating_keeps_the_current_alias_valid(): void
    {
        $link = Link::factory()->for($this->user)->create(['code' => 'mine']);

        $this->patchJson("/api/links/{$link->id}", ['alias' => 'mine', 'original_url' => 'https://example.org'])
            ->assertOk();
    }

    public function test_user_cannot_touch_someone_elses_link(): void
    {
        $link = Link::factory()->create();

        $this->getJson("/api/links/{$link->id}")->assertForbidden();
        $this->patchJson("/api/links/{$link->id}", ['is_active' => false])->assertForbidden();
        $this->deleteJson("/api/links/{$link->id}")->assertForbidden();
    }

    public function test_admin_can_view_any_link(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $link = Link::factory()->create();

        $this->getJson("/api/links/{$link->id}")->assertOk();
    }
}
