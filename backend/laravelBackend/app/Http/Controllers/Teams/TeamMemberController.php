<?php
// app/Http\Controllers/Teams/TeamMemberController.php
namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use App\Events\TeamMemberAdded;
use Str;

class TeamMemberController extends Controller
{
    public function addMembersToTeam(Request $request,$team_id)
    {
        $validated = $request->validate([
            'workspace_member_ids' => 'required|array',
            'workspace_member_ids.*' => 'uuid|exists:workspace_members,workspace_member_id',
        ]);

        $team = Team::findOrFail($team_id);
        $workspace_id = $team->workspace_id;
        // dd($team);

        // Get workspace members and resolve user_ids
        $members = WorkspaceMember::whereIn('workspace_member_id', $validated['workspace_member_ids'])
            ->where('workspace_id', $workspace_id)
            ->with('user')
            ->get();
        // dd('', $members);
        $added = [];
        foreach ($members as $member) {
            // Check if user is already a team member
            $exists = TeamMember::where('team_id', $team->team_id)
                ->where('user_id', $member->user_id)
                ->exists();

            if (!$exists) {
                TeamMember::create([
                    'team_member_id' => Str::uuid(),
                    'team_id' => $team->team_id,
                    'user_id' => $member->user_id,
                    'role' => 'member',     
                    'joined_at' => now(),
                ]);

                
                event(new TeamMemberAdded(
                    $team,
                    $member->user_id,
                    auth()->id()
                ));

                $added[] = $member->user_id;
            }
        }

        return response()->json([
            'message' => 'Members added successfully',
            'added_members' => $added,
        ]);
    }

    public function getWorkspaceUsers($workspace_id)
    {
        try {
            $workspace = Workspace::with('members.user')->findOrFail($workspace_id);

            $users = $workspace->members->map(function ($member) {
                return [
                    'workspace_member_id' => $member->workspace_member_id,
                    'user_id' => $member->user->user_id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                ];
            });

            return response()->json(['users' => $users]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch workspace users',
                'details' => $e->getMessage()
            ], 500);
        }
    }

}