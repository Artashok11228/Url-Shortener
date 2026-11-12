<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LinkAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_links_but_public_redirect_still_works(): void
    {
        $link = Link::factory()->create(['code' => 'public1']);

        $this->getJson('/api/links')->assertUnauthorized();
        $this->postJson('/api/links', ['original_url' => 'https://example.com'])->assertUnauthorized();
        $this->getJson("/api/links/{$link->id}")->assertUnauthorized();
        $this->patchJson("/api/links/{$link->id}", ['is_active' => false])->assertUnauthorized();
        $this->deleteJson("/api/links/{$link->id}")->assertUnauthorized();

        $this->get('/public1')->assertRedirect($link->original_url);
    }

    public function test_policy_matrix_for_owner_other_user_and_admin(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $link = Link::factory()->for($owner)->create();

        $this->assertTrue($owner->can('viewAny', Link::class));
        $this->assertTrue($owner->can('create', Link::class));
        $this->assertTrue($owner->can('view', $link));
        $this->assertTrue($owner->can('update', $link));
        $this->assertTrue($owner->can('delete', $link));

        $this->assertTrue($other->can('viewAny', Link::class));
        $this->assertTrue($other->can('create', Link::class));
        $this->assertFalse($other->can('view', $link));
        $this->assertFalse($other->can('update', $link));
        $this->assertFalse($other->can('delete', $link));

        $this->assertTrue($admin->can('viewAny', Link::class));
        $this->assertTrue($admin->can('create', Link::class));
        $this->assertTrue($admin->can('view', $link));
        $this->assertTrue($admin->can('update', $link));
        $this->assertTrue($admin->can('delete', $link));
    }

    public function test_user_list_does_not_expose_other_users_links(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Link::factory()->for($user)->create(['code' => 'mine-01']);
        Link::factory()->create(['code' => 'theirs1']);

        $response = $this->getJson('/api/links')->assertOk()->assertJsonCount(1, 'data');

        $this->assertSame('mine-01', $response->json('data.0.code'));
        $this->assertStringNotContainsString('theirs1', $response->getContent());
    }

    public function test_admin_can_list_and_manage_any_link(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $link = Link::factory()->for($owner)->create(['code' => 'owned']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/links')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'owned');

        $this->getJson("/api/links/{$link->id}")->assertOk();

        $this->patchJson("/api/links/{$link->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/links/{$link->id}")->assertNoContent();
        $this->assertSoftDeleted($link);
    }

    public function test_request_body_cannot_reassign_link_ownership(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $link = Link::factory()->for($owner)->create();

        Sanctum::actingAs($attacker);

        $this->patchJson("/api/links/{$link->id}", [
            'user_id' => $attacker->id,
            'is_active' => false,
        ])->assertForbidden();

        Sanctum::actingAs($owner);

        $this->patchJson("/api/links/{$link->id}", [
            'user_id' => $attacker->id,
            'original_url' => 'https://example.org/kept',
        ])->assertOk();

        $link->refresh();

        $this->assertSame($owner->id, $link->user_id);
        $this->assertSame('https://example.org/kept', $link->original_url);
    }

    public function test_store_always_assigns_the_authenticated_user_as_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/links', [
            'original_url' => 'https://example.com/owned',
            'user_id' => $other->id,
        ])->assertCreated();

        $this->assertDatabaseHas('links', [
            'id' => $response->json('data.id'),
            'user_id' => $user->id,
            'original_url' => 'https://example.com/owned',
        ]);
    }
}
