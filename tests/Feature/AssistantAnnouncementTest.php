<?php

namespace Tests\Feature;

use App\Models\AssistantAction;
use App\Models\User;
use App\Support\Assistant\Assistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_announcement_command_always_returns_confirmation_card(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $result = Assistant::ask($admin, 'Send announcement to all tenants: “Live push notification test. Please disregard.”');

        $this->assertCount(1, $result['actions']);
        $this->assertSame('Post announcement', $result['actions'][0]['title']);
        $this->assertSame('pending', $result['actions'][0]['status']);
        $this->assertDatabaseHas('assistant_actions', ['user_id' => $admin->id, 'type' => 'post_announcement', 'status' => 'pending']);
        $this->assertDatabaseCount('announcements', 0);
    }
}
