<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'owner_id' => \App\Models\User::factory(),
            'created_at' => now(),
        ];
    }
}
