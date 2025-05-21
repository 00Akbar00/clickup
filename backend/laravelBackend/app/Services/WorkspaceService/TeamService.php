<?php

namespace App\Services\WorkspaceService;

use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamService
{
    // Create new team
    public function createTeam(array $data, string $workspace_id, string $user_id)
    {
        // Start transaction in case something fails
        return DB::transaction(function () use ($data, $workspace_id, $user_id) {
            // Create the team
            $team = Team::create([
                'team_id' => (string) Str::uuid(),
                'workspace_id' => $workspace_id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'visibility' => $data['visibility'] ?? 'private',
                'color_code' => $data['color_code'] ?? null,
                'created_by' => $user_id,
            ]);

            // Add the creator as the team owner
            DB::table('team_members')->insert([
                'team_member_id' => (string) Str::uuid(),
                'team_id' => $team->team_id,
                'user_id' => $user_id,
                'role' => 'owner',
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $team;
        });
    }
    // Get teams in a workspace
    public function getTeams(string $workspace_id)
    {
        return Team::where('workspace_id', $workspace_id)->get();
    }

    // Get single team details
    public function getTeamDetails(string $team_id)
    {
        return Team::with('creator')->findOrFail($team_id);
    }

    // Update team
    public function updateTeam(string $team_id, array $data, string $user_id)
    {
        $team = Team::findOrFail($team_id);

        if ($team->created_by !== $user_id) {
            throw new \Exception('Forbidden: You do not own this team.', 403);
        }

        $team->update($data);

        return $team;
    }

    // Delete team
    public function deleteTeam(string $team_id, string $user_id): void
    {
        $team = Team::findOrFail($team_id);

        // Only allow the team creator (owner) to delete
        if ($team->created_by !== $user_id) {
            throw new \Exception('Forbidden: You do not own this team.', 403);
        }

        $team->delete();
    }

    // Get members if needed
    public function getTeamMembers(string $team_id)
    {
        $team = Team::with('members')->findOrFail($team_id);
        return $team->members;
    }
}
