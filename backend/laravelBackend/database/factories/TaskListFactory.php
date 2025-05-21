<?php

namespace Database\Factories;

use App\Models\TaskList;
use App\Models\Project; // Assuming App\Models\Project
use App\Models\User;    // Assuming App\Models\User
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TaskListFactory extends Factory
{
    protected $model = TaskList::class;

    public function definition()
    {
        return [
            'list_id' => (string) Str::uuid(),
            'project_id' => Project::factory(), // Creates a Project if not provided
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'created_by' => User::factory(), // Creates a User if not provided
            'status' => 'active', // Default status
        ];
    }
}
