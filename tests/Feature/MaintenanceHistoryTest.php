<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\Room;
use App\Models\User;
use App\Support\WebPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MaintenanceHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(User $panday): MaintenanceRequest
    {
        $room = Room::create(['room_number' => '9', 'floor' => 1, 'type' => 'single', 'capacity' => 1, 'monthly_rate' => 3000, 'status' => 'available']);

        return MaintenanceRequest::create([
            'room_id' => $room->id, 'description' => 'Replace leaking faucet', 'priority' => 'medium',
            'status' => 'resolved', 'resolution_details' => 'Installed a new faucet.', 'completion_date' => today(),
        ])->forceFill(['assigned_to' => $panday->id, 'tools_returned_at' => now(), 'tools_verified_at' => now()]);
    }

    public function test_shared_history_is_visible_but_panday_only_sees_own_work(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $caretaker = User::factory()->create(['role' => 'caretaker']);
        $panday = User::factory()->create(['role' => 'maintenance']);
        $otherPanday = User::factory()->create(['role' => 'maintenance']);
        $ticket = $this->ticket($panday); $ticket->save();

        $this->actingAs($admin)->get(route('admin.maintenance-history.index'))->assertOk()->assertSee('Replace leaking faucet');
        $this->actingAs($caretaker)->get(route('admin.maintenance-history.index'))->assertOk()->assertSee('Replace leaking faucet');
        $this->actingAs($panday)->get(route('admin.maintenance-history.index'))->assertOk()->assertSee('Replace leaking faucet');
        $this->actingAs($otherPanday)->get(route('admin.maintenance-history.index'))->assertOk()->assertDontSee('Replace leaking faucet');
    }

    public function test_only_assigned_panday_can_trigger_admin_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $panday = User::factory()->create(['role' => 'maintenance']);
        $ticket = $this->ticket($panday); $ticket->save();
        $this->mock(WebPush::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendToUsers')->once()->andReturn(['sent' => 0, 'failed' => 0, 'removed' => 0]);
        });

        $this->actingAs($panday)->post(route('admin.maintenance-history.trigger-admin', $ticket))->assertRedirect();
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id]);

        $this->actingAs($admin)->post(route('admin.maintenance-history.trigger-admin', $ticket))->assertForbidden();
    }
}
