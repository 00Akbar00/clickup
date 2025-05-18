<?php
// app/Http\Controllers/Teams/TeamMemberController.php
namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Events\TeamMemberAdded;

class TeamMemberController extends Controller
{
    public function addMember(Request $request)
    {
        $validated = $request->validate([
            'team_id' => 'required|uuid|exists:teams,team_id',
            'user_id' => 'required|uuid|exists:users,user_id',
        ]);

        $team = Team::findOrFail($validated['team_id']);

        if ($team->members()->where('user_id', $validated['user_id'])->exists()) {
            return response()->json(['error' => 'User already a member of the team'], 409);
        }

        $team->members()->attach($validated['user_id'], [
            'added_by' => auth()->id(),
        ]);

        // Dispatch the event
        event(new TeamMemberAdded(
            $team,
            $validated['user_id'],
            auth()->id()
        ));

        return response()->json(['message' => 'User added to team']);
    }
}