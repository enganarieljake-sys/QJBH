<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DashboardPeople;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardPeopleTest extends TestCase
{
    use RefreshDatabase;

    public function test_people_are_ordered_for_admin_caretaker_and_tenant_dashboards(): void
    {
        $admin = User::factory()->create(['name' => 'Ate Jackie', 'role' => 'admin']);
        $caretaker = User::factory()->create(['name' => 'Caretaker Joy', 'role' => 'caretaker']);
        $maintenance = User::factory()->create(['name' => 'Panday Ben', 'role' => 'maintenance']);
        $tenantOnline = User::factory()->create(['name' => 'Tenant Online', 'role' => 'tenant', 'tenant_approved_at' => now()]);
        $tenantOffline = User::factory()->create(['name' => 'Tenant Offline', 'role' => 'tenant', 'tenant_approved_at' => now()]);
        $tenantRecent = User::factory()->create(['name' => 'Tenant Recent', 'role' => 'tenant', 'tenant_approved_at' => now()]);
        $this->recordSession($tenantOnline, now()->timestamp);
        $this->recordSession($tenantRecent, now()->subMinutes(10)->timestamp);
        $this->recordSession($tenantOffline, now()->subHours(2)->timestamp);

        $this->assertSame(
            ['Tenant Online', 'Tenant Recent', 'Tenant Offline', 'Caretaker Joy', 'Panday Ben'],
            DashboardPeople::for($admin)->pluck('name')->all(),
        );
        $this->assertSame(
            ['Ate Jackie', 'Tenant Online', 'Tenant Recent', 'Tenant Offline', 'Panday Ben'],
            DashboardPeople::for($caretaker)->pluck('name')->all(),
        );
        $this->assertSame(
            ['Ate Jackie', 'Caretaker Joy', 'Panday Ben'],
            DashboardPeople::for($tenantOffline)->pluck('name')->all(),
        );
    }

    public function test_presence_uses_compact_time_and_admin_gets_add_user_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tenant = User::factory()->create(['name' => 'Maria Tenant', 'role' => 'tenant', 'tenant_approved_at' => now()]);
        $this->recordSession($tenant, now()->subMinutes(12)->timestamp);

        $person = DashboardPeople::for($admin)->firstWhere('id', $tenant->id);
        $this->assertSame('12 min ago', $person->presenceShortLabel());

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Who’s around')
            ->assertSee(route('admin.users.create'));
    }

    private function recordSession(User $user, int $lastActivity): void
    {
        DB::table('sessions')->insert([
            'id' => 'test-'.$user->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => $lastActivity,
        ]);
    }
}
