<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;

class PushService
{
    /**
     * Send a push notification to all devices of a user.
     */
    public function sendToUser(string $userId, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->get(['device_token', 'device_type']);

        foreach ($tokens as $token) {
            if ($token->device_type === 'ios') {
                $this->sendApn($token->device_token, $title, $body, $data);
            } else {
                $this->sendFcm($token->device_token, $title, $body, $data);
            }
        }
    }

    protected function sendFcm(string $token, string $title, string $body, array $data = []): void
    {
        Http::withToken(config('services.fcm.server_key'))
            ->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => $data,
            ]);
    }

    protected function sendApn(string $token, string $title, string $body, array $data = []): void
    {
        $payload = [
            'aps' => [
                'alert' => [
                    'title' => $title,
                    'body'  => $body,
                ],
            ],
        ];

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        Http::withHeaders([
                'apns-topic' => config('services.apn.topic'),
            ])
            ->withToken(config('services.apn.auth_token'))
            ->post("https://api.push.apple.com/3/device/{$token}", $payload);
    }
}
