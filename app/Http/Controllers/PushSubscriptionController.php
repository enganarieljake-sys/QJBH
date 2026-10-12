<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Support\WebPush;

class PushSubscriptionController extends Controller
{
    public function config(Request $request, WebPush $webPush): JsonResponse
    {
        $configured = $webPush->configured();

        return response()->json([
            'enabled' => $configured,
            'publicKey' => $configured ? config('services.webpush.public_key') : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:4096'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:256'],
            'contentEncoding' => ['nullable', 'in:aes128gcm,aesgcm'],
        ]);

        $endpoint = $data['endpoint'];
        $this->assertTrustedEndpoint($endpoint);
        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $endpoint)],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $endpoint,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'device_label' => $this->deviceLabel((string) $request->userAgent()),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'failure_count' => 0,
            ]
        );

        return response()->json(['enabled' => true], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'url:https', 'max:4096']]);
        $this->assertTrustedEndpoint($data['endpoint']);
        $request->user()->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))
            ->delete();

        return response()->json(['enabled' => false]);
    }

    public function test(Request $request, WebPush $webPush): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'url:https', 'max:4096']]);
        $this->assertTrustedEndpoint($data['endpoint']);
        $subscription = $request->user()->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))
            ->firstOrFail();

        $delivery = $webPush->sendToSubscriptions(collect([$subscription]), [
            'title' => 'QJBH notifications are ready',
            'body' => 'You will now receive important QJBH updates on this phone.',
            'url' => route('notifications.index', absolute: false),
            'tag' => 'qjbh-push-test',
            'icon' => '/icons/icon-192.png',
            'badge' => '/icons/icon-192.png',
        ]);

        if ($delivery['sent'] < 1) {
            return response()->json([
                'message' => 'The phone was registered, but the test push could not be delivered. Please check your phone notification settings.',
                'delivery' => $delivery,
            ], 422);
        }

        return response()->json(['message' => 'Test notification sent.', 'delivery' => $delivery]);
    }

    private function deviceLabel(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'iPhone') => 'iPhone PWA',
            str_contains($agent, 'iPad') => 'iPad PWA',
            str_contains($agent, 'Android') => 'Android device',
            default => 'Web browser',
        };
    }

    private function assertTrustedEndpoint(string $endpoint): void
    {
        $host = strtolower((string) parse_url($endpoint, PHP_URL_HOST));
        $trustedSuffixes = [
            'googleapis.com', 'google.com', 'mozilla.com', 'apple.com',
            'windows.com', 'microsoft.com',
        ];

        $trusted = collect($trustedSuffixes)->contains(
            fn (string $suffix) => $host === $suffix || str_ends_with($host, '.'.$suffix)
        );

        if (! $trusted) {
            throw ValidationException::withMessages(['endpoint' => 'The push service endpoint is not trusted.']);
        }
    }
}
