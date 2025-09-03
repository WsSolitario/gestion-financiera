<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BrevoClient
{
    public static function sendInvitation(string $email, string $token): void
    {
        $appUrl = config('app.url');
        $link = rtrim($appUrl, '/') . "/invitations/token/{$token}";
        $name = Str::before($email, '@');

        $html = view('emails.invitation', [
            'name' => $name,
            'link' => $link,
        ])->render();

        Http::withHeaders([
            'api-key' => config('brevo.api_key'),
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'to' => [
                ['email' => $email],
            ],
            'htmlContent' => $html,
        ]);
    }
}

