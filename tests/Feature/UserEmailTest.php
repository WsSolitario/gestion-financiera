<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_emails_are_unique_case_insensitively(): void
    {
        User::factory()->create(['email' => 'User@example.com']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'user@example.com']);
    }
}

