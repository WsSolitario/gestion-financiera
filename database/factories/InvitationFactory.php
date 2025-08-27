<?php

namespace Database\Factories;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'inviter_id' => \App\Models\User::factory(),
            'invitee_email' => $this->faker->safeEmail(),
            'group_id' => \App\Models\Group::factory(),
            'token' => Str::random(40),
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(7),
        ];
    }
}
