<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppModeControllerTest extends TestCase
{
    public function test_show_returns_private_mode(): void
    {
        config(['app.mode_app' => 'private']);

        $response = $this->getJson('/api/app-mode');

        $response->assertStatus(200)
            ->assertExactJson(['mode_app' => 'private']);
    }

    public function test_show_returns_public_mode(): void
    {
        config(['app.mode_app' => 'public']);

        $response = $this->getJson('/api/app-mode');

        $response->assertStatus(200)
            ->assertExactJson(['mode_app' => 'public']);
    }
}

