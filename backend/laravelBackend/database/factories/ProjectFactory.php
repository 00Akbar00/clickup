<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Team;
use App\Models\User; // Ensure this is your correct User model
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition()
    {
        return [
            'project_id' => (string) Str::uuid(),
            'team_id' => Team::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph,
            // Ensure a valid User ID is provided.
            // Using a closure ensures a new User is created and its key is used
            // when this factory is called without an explicit 'created_by' override.
            'created_by' => function () {
                return User::factory()->create()->getKey();
            },
            'visibility' => $this->faker->randomElement(['public', 'private', 'team_only']), // Verify these values
            'status' => $this->faker->randomElement(['active', 'pending', 'on_hold', 'completed', 'archived']), // Verify these values
            'color_code' => $this->faker->optional()->hexColor,
        ];
    }

    public function public()
    {
        return $this->state(function (array $attributes) {
            return [
                'visibility' => 'public',
            ];
        });
    }

    public function private()
    {
        return $this->state(function (array $attributes) {
            return [
                'visibility' => 'private',
            ];
        });
    }
}
