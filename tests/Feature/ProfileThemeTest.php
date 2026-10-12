<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::flushCache();
    }

    public function test_every_role_can_see_and_save_a_personal_app_color(): void
    {
        foreach (User::ROLES as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('profile.edit'))
                ->assertOk()
                ->assertSee('My app color')
                ->assertSee('name="dashboard_color"', false);

            $this->actingAs($user)->put(route('profile.update'), [
                'name' => $user->name,
                'dashboard_color' => 'purple',
            ])->assertRedirect(route('profile.edit'));

            $this->assertSame('purple', $user->fresh()->dashboardColor());
        }
    }

    public function test_app_colors_are_isolated_between_users(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant']);
        $caretaker = User::factory()->create(['role' => 'caretaker']);

        Setting::put('dashboard_color_user_'.$tenant->id, 'green');

        $this->assertSame('green', $tenant->dashboardColor());
        $this->assertSame('blue', $caretaker->dashboardColor());
    }
}
