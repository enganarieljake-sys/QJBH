<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use App\Support\WebPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class WebPushNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_register_and_remove_own_device_subscription(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'tenant_approved_at' => now()]);
        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/subscription/one',
            'keys' => ['p256dh' => str_repeat('a', 87), 'auth' => str_repeat('b', 22)],
            'contentEncoding' => 'aes128gcm',
        ];

        $this->actingAs($tenant)->postJson(route('push.subscriptions.store'), $payload)
            ->assertCreated()->assertJson(['enabled' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $tenant->id,
            'endpoint_hash' => hash('sha256', $payload['endpoint']),
        ]);

        $this->actingAs($tenant)->deleteJson(route('push.subscriptions.destroy'), ['endpoint' => $payload['endpoint']])
            ->assertOk()->assertJson(['enabled' => false]);
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_admin_can_register_push_subscription(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson(route('push.subscriptions.store'), [
            'endpoint' => 'https://fcm.googleapis.com/subscription/admin',
            'keys' => ['p256dh' => str_repeat('a', 87), 'auth' => str_repeat('b', 22)],
        ])->assertCreated();

        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $admin->id]);
    }

    public function test_tenant_can_send_a_test_push_only_to_their_own_devices(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'tenant_approved_at' => now()]);
        $subscription = PushSubscription::create([
            'user_id' => $tenant->id,
            'endpoint' => 'https://fcm.googleapis.com/subscription/test-phone',
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/subscription/test-phone'),
            'public_key' => str_repeat('a', 87),
            'auth_token' => str_repeat('b', 22),
            'content_encoding' => 'aes128gcm',
        ]);

        $this->mock(WebPush::class, function (MockInterface $mock) use ($subscription): void {
            $mock->shouldReceive('sendToSubscriptions')->once()
                ->withArgs(fn ($subscriptions, $payload) => $subscriptions->pluck('id')->all() === [$subscription->id] && $payload['tag'] === 'qjbh-push-test')
                ->andReturn(['sent' => 1, 'failed' => 0, 'removed' => 0]);
        });

        $this->actingAs($tenant)->postJson(route('push.test'), ['endpoint' => $subscription->endpoint])
            ->assertOk()->assertJsonPath('delivery.sent', 1);
    }

    public function test_user_cannot_test_another_users_device(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'tenant_approved_at' => now()]);
        $other = User::factory()->create(['role' => 'tenant', 'tenant_approved_at' => now()]);
        $endpoint = 'https://fcm.googleapis.com/subscription/other-phone';
        PushSubscription::create([
            'user_id' => $other->id, 'endpoint' => $endpoint, 'endpoint_hash' => hash('sha256', $endpoint),
            'public_key' => str_repeat('a', 87), 'auth_token' => str_repeat('b', 22), 'content_encoding' => 'aes128gcm',
        ]);

        $this->actingAs($tenant)->postJson(route('push.test'), ['endpoint' => $endpoint])->assertNotFound();
    }

    public function test_untrusted_push_endpoint_is_rejected(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'tenant_approved_at' => now()]);
        $this->actingAs($tenant)->postJson(route('push.subscriptions.store'), [
            'endpoint' => 'https://example.test/internal',
            'keys' => ['p256dh' => str_repeat('a', 87), 'auth' => str_repeat('b', 22)],
        ])->assertUnprocessable()->assertJsonValidationErrors('endpoint');
    }

    public function test_announcement_creates_history_and_requests_push_for_approved_tenants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tenant = User::factory()->create(['role' => 'tenant', 'tenant_approved_at' => now()]);
        User::factory()->create(['role' => 'tenant', 'tenant_approved_at' => null]);

        $this->mock(WebPush::class, function (MockInterface $mock) use ($admin, $tenant): void {
            $mock->shouldReceive('sendToUsers')->twice()
                ->withArgs(fn ($users, $payload) => in_array($users->pluck('id')->all(), [[$tenant->id], [$admin->id]], true) && str_starts_with($payload['tag'], 'qjbh-announcement-'))
                ->andReturn(['sent' => 1, 'failed' => 0, 'removed' => 0]);
        });

        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Water interruption',
            'body' => 'Water service resumes at 4 PM.',
        ])->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $tenant->id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id]);
        $this->actingAs($tenant)->get(route('notifications.index'))
            ->assertOk()->assertSee('Water interruption');
    }
}
