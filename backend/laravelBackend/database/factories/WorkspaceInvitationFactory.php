<?php

namespace Database\Factories;

use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\User; // If you need to associate inviter_id for example
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WorkspaceInvitationFactory extends Factory
{
    protected $model = WorkspaceInvitation::class;

    public function definition()
    {
        return [
            'invitation_id' => (string) Str::uuid(),
            'workspace_id' => Workspace::factory(), // Creates a workspace if not provided
            'email' => $this->faker->unique()->safeEmail,
            'role' => $this->faker->randomElement(['member', 'admin']),
            'inviter_name' => $this->faker->name, // Or link to a User model: User::factory()->create()->full_name
            'invite_token' => Str::random(32),
            'expires_at' => Carbon::now()->addDays(7),
            'status' => 'pending', // pending, accepted, expired
        ];
    }
}
