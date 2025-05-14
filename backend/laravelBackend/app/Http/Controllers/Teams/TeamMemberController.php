<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class TeamMemberController extends Controller
{
    // Add user to a team
    public function addMember(Request $request)
    {
        $validated = $request->validate([
            'team_id' => 'required|uuid|exists:teams,team_id',
            'user_id' => 'required|uuid|exists:users,user_id',
            // 'role' => 'required|string|in:owner,admin,member,guest',
        ]);

        $team = Team::findOrFail($validated['team_id']);

        // Checking if user exists in the team
        if ($team->members()->where('user_id', $validated['user_id'])->exists()) {
            return response()->json(['error' => 'User already a member of the team'], 409);
        }

        $team->members()->attach($validated['user_id'], [
            'added_by' => auth()->user()->user_id,
        ]);

        return response()->json(['message' => 'User added to team']);
    }
}