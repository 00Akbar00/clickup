<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember; // Ensure this model exists and is correct
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon; // If you use Carbon for 'joined_at', though 'now()' is fine

class WorkspaceMemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WorkspaceMember::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Ensure User and Workspace factories correctly handle their primary keys (user_id, workspace_id)
        // The ->create() here will generate new User and Workspace for each WorkspaceMember by default.
        // If you want to use existing ones in your tests, you'll pass them when calling the factory.
        $workspaceId = Workspace::factory()->create()->workspace_id;
        $userId = User::factory()->create()->user_id;

        return [
            'workspace_member_id' => (string) Str::uuid(),
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'role' => $this->faker->randomElement(['owner', 'admin', 'member']), // Common roles
            'joined_at' => now(),
        ];
    }

    /**
     * Indicate that the workspace member is an owner.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function owner()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'owner',
            ];
        });
    }

    /**
     * Indicate that the workspace member is an admin.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'admin',
            ];
        });
    }

    /**
     * Indicate that the workspace member is a regular member.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function member()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'member',
            ];
        });
    }
}
