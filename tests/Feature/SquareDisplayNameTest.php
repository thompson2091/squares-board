<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Square;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SquareDisplayNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_display_name_on_open_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->open()->create();
        $square = Square::factory()->create([
            'board_id' => $board->id,
            'user_id' => $user->id,
            'row' => 0,
            'col' => 0,
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson("/boards/{$board->uuid}/squares/{$square->id}/name", [
                'display_name' => 'Custom Name',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'square' => [
                'id' => $square->id,
                'display_name' => 'Custom Name',
            ],
        ]);

        $this->assertDatabaseHas('squares', [
            'id' => $square->id,
            'display_name' => 'Custom Name',
        ]);
    }

    public function test_owner_can_clear_display_name(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->open()->create();
        $square = Square::factory()->create([
            'board_id' => $board->id,
            'user_id' => $user->id,
            'display_name' => 'Old Name',
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson("/boards/{$board->uuid}/squares/{$square->id}/name", [
                'display_name' => null,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('squares', [
            'id' => $square->id,
            'display_name' => null,
        ]);
    }

    public function test_board_admin_can_update_display_name(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $board = Board::factory()->open()->create(['owner_id' => $owner->id]);
        $board->admins()->attach($admin);

        $square = Square::factory()->create([
            'board_id' => $board->id,
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patchJson("/boards/{$board->uuid}/squares/{$square->id}/name", [
                'display_name' => 'Admin Set Name',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('squares', [
            'id' => $square->id,
            'display_name' => 'Admin Set Name',
        ]);
    }

    public function test_platform_admin_can_update_display_name(): void
    {
        $platformAdmin = User::factory()->create(['role' => 'platform_admin']);
        $user = User::factory()->create();

        $board = Board::factory()->open()->create();
        $square = Square::factory()->create([
            'board_id' => $board->id,
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($platformAdmin)
            ->patchJson("/boards/{$board->uuid}/squares/{$square->id}/name", [
                'display_name' => 'Platform Admin Set',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('squares', [
            'id' => $square->id,
            'display_name' => 'Platform Admin Set',
        ]);
    }

    public function test_unauthorized_user_cannot_update_display_name(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $board = Board::factory()->open()->create();
        $square = Square::factory()->create([
            'board_id' => $board->id,
            'user_id' => $owner->id,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->patchJson("/boards/{$board->uuid}/squares/{$square->id}/name", [
                'display_name' => 'Hacker Name',
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_update_display_name_on_locked_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->locked()->create();
        $square = Square::factory()->create([
            'board_id' => $board->id,
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson("/boards/{$board->uuid}/squares/{$square->id}/name", [
                'display_name' => 'Too Late',
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_update_display_name_on_unclaimed_square(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->open()->create(['owner_id' => $user->id]);
        $square = Square::factory()->create([
            'board_id' => $board->id,
            'user_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson("/boards/{$board->uuid}/squares/{$square->id}/name", [
                'display_name' => 'Unclaimed',
            ]);

        $response->assertStatus(400);
    }

    public function test_release_clears_display_name(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->open()->create();
        $square = Square::factory()->create([
            'board_id' => $board->id,
            'user_id' => $user->id,
            'display_name' => 'Custom Name',
            'is_paid' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->deleteJson("/boards/{$board->uuid}/squares/{$square->id}/release");

        $response->assertOk();
        $this->assertDatabaseHas('squares', [
            'id' => $square->id,
            'user_id' => null,
            'display_name' => null,
        ]);
    }

    public function test_display_name_for_square_accessor_returns_custom_name(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $square = Square::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'Johnny',
        ]);

        $this->assertEquals('Johnny', $square->displayNameForSquare);
    }

    public function test_display_name_for_square_accessor_falls_back_to_user_name(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $square = Square::factory()->create([
            'user_id' => $user->id,
            'display_name' => null,
        ]);

        $this->assertEquals('John Doe', $square->displayNameForSquare);
    }

    public function test_guest_cannot_update_display_name(): void
    {
        $board = Board::factory()->open()->create();
        $square = Square::factory()->create([
            'board_id' => $board->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->patchJson("/boards/{$board->uuid}/squares/{$square->id}/name", [
            'display_name' => 'Guest Attempt',
        ]);

        $response->assertUnauthorized();
    }
}
