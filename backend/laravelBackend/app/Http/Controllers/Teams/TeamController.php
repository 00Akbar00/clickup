<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Str;

class TeamController extends Controller
{
    // Create a new Team
    public function createTeam(Request $request,$workspace_id)
    {
        $validator = Validator::make($request->all(), [
            
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'in:public,private',
            'color_code' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $userId = auth()->id();

            $team = Team::create([
                'team_id' => (string) Str::uuid(),
                'workspace_id' => $workspace_id,
                'name' => $request->name,
                'description' => $request->description,
                'visibility' => $request->visibility ?? 'private',
                'created_by' => $userId, 
                'color_code' => $request->color_code,
            ]);

            return response()->json(['message' => 'Team created successfully', 'team' => $team], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create team', 'details' => $e->getMessage()], 500);
        }
    }

    // All teams in workspace
    public function getTeams(Request $request, $workspace_id)
    {
        try {
            $teams = Team::where('workspace_id', $workspace_id)->get();
            return response()->json($teams);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve teams'], 500);
        }
    }

    //Get Team Details
    public function getTeamDetails($team_id)
    {
        try {
            $team = Team::with(['creator'])->findOrFail($team_id);
            return response()->json($team);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Team not found'], 404);
        }
    }

    //Update Team
    public function updateTeam(Request $request, $team_id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'in:public,private',
            'color_code' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $team = Team::findOrFail($team_id);

            $team->update($request->only(['name', 'description', 'visibility', 'color_code']));

            return response()->json(['message' => 'Team updated successfully', 'team' => $team]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Team not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update team'], 500);
        }
    }

    //Delete Team
    public function deleteTeam($team_id)
    {
        try {
            $team = Team::findOrFail($team_id);
            $team->delete();
            return response()->json(['message' => 'Team deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Team not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete team'], 500);
        }
    }

    //Get Team Members
    public function getTeamMembers($team_id)
    {
        try {
            $team = Team::with('members')->findOrFail($team_id);
            return response()->json($team->members);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Team not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get members'], 500);
        }
    }

}