<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use App\Models\Workspace; // Make sure you have this model and its factory
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition()
    {
        return [
            'team_id' => (string) Str::uuid(), // Your model's boot method also handles this
            'workspace_id' => Workspace::factory(),
            'name' => $this->faker->words(2, true) . ' Team',
            'description' => $this->faker->optional()->sentence,
            /**
             * IMPORTANT: Defaulting 'visibility' to 'private'.
             * You MUST check your 'teams' table migration for the actual CHECK constraint
             * or enum definition for the 'visibility' column and use an allowed value.
             * If 'private' is not allowed, this will still fail.
             * Common alternatives might be 'public', or other specific terms.
             */
            'visibility' => 'private',
            'created_by' => User::factory(),
            'color_code' => $this->faker->optional()->hexColor,
        ];
    }

    /**
     * Indicate that the team is private.
     * This state aligns with the current default assumption.
     */
    public function private()
    {
        return $this->state(function (array $attributes) {
            return [
                'visibility' => 'private',
            ];
        });
    }

    /**
     * Example: Indicate that the team is public.
     * Only use/uncomment this if 'public' is a confirmed allowed value in your DB schema for teams.visibility.
     */
    // public function public()
    // {
    //     return $this->state(function (array $attributes) {
    //         return [
    //             'visibility' => 'public', // Ensure 'public' is allowed by your DB constraint
    //         ];
    //     });
    // }


    public function createdBy(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'created_by' => $user->id,
            ];
        });
    }

    public function forWorkspace(Workspace $workspace)
    {
        return $this->state(function (array $attributes) use ($workspace) {
            return [
                'workspace_id' => $workspace->workspace_id,
            ];
        });
    }
}
