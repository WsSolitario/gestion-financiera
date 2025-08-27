<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\DeviceToken;
use App\Services\PushService;

class PushServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_notifications_to_all_platforms(): void
    {
        config([
            'services.fcm.server_key' => 'key',
            'services.apn.auth_token' => 'token',
            'services.apn.topic' => 'com.example.app',
        ]);

        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Tester',
            'email' => 'tester@example.com',
        ]);

        DeviceToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'device_token' => 'android-token',
            'device_type' => 'android',
        ]);

        DeviceToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'device_token' => 'ios-token',
            'device_type' => 'ios',
        ]);

        Http::fake();

        $service = new PushService();
        $service->sendToUser($user->id, 'Hola', 'Mundo');

        Http::assertSent(fn($req) =>
            $req->url() === 'https://fcm.googleapis.com/fcm/send'
            && $req['to'] === 'android-token'
        );

        Http::assertSent(fn($req) =>
            $req->url() === 'https://api.push.apple.com/3/device/ios-token'
        );

        Http::assertSentCount(2);
    }
}
