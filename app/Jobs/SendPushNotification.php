<?php

namespace App\Jobs;

use App\Services\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $userId;
    public string $title;
    public string $body;
    public array $data;

    public function __construct(string $userId, string $title, string $body, array $data = [])
    {
        $this->userId = $userId;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    public function handle(PushService $push): void
    {
        $push->sendToUser($this->userId, $this->title, $this->body, $this->data);
    }
}
