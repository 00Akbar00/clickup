<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    public function definition()
    {
        return [
            'workspace_id' => (string) Str::uuid(),
            'name' => $this->faker->company,
            'description' => $this->faker->optional()->sentence,
            'created_by' => User::factory(), // Assumes UserFactory exists
            'invite_token' => (string) Str::uuid(),
            'invite_token_expires_at' => Carbon::now()->addDays(7),
            'logo_url' => $this->faker->imageUrl(200, 200, 'business'), // Placeholder
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
