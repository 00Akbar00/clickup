<?php

namespace App\Services\WorkspaceService;

use App\Models\Team;
use Illuminate\Support\Str;

class TeamService
{
    // Create new team
    public function createTeam(array $data, string $workspace_id, string $user_id)
    {
        return Team::create([
            'team_id'      => (string) Str::uuid(),
            'workspace_id' => $workspace_id,
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'visibility'   => $data['visibility'] ?? 'private',
            'color_code'   => $data['color_code'] ?? null,
            'created_by'   => $user_id,
        ]);
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
