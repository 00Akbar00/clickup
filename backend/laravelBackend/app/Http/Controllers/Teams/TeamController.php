<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Services\WorkspaceService\TeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TeamController extends Controller
{
    protected $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    // Create a new Team
    public function createTeam(Request $request, $workspace_id)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'in:public,private',
            'color_code' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $team = $this->teamService->createTeam($request->all(), $workspace_id, auth()->id());
            return response()->json(['message' => 'Team created successfully', 'team' => $team], 201);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to create team', 'details' => $e->getMessage()], 500);
        }
    }

    // Get all teams in a workspace
    public function getTeams($workspace_id)
    {
        try {
            $teams = $this->teamService->getTeams($workspace_id);
            return response()->json($teams);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to retrieve teams', 'details' => $e->getMessage()], 500);
        }
    }

    // Get details of a team
    public function getTeamDetails($team_id)
    {
        try {
            $team = $this->teamService->getTeamDetails($team_id);
            return response()->json($team);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Team not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to retrieve team details', 'details' => $e->getMessage()], 500);
        }
    }

    // Update team
    public function updateTeam(Request $request, $team_id)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'in:public,private',
            'color_code' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $team = $this->teamService->updateTeam($team_id, $request->only(['name', 'description', 'visibility', 'color_code']), auth()->id());
            return response()->json(['message' => 'Team updated successfully', 'team' => $team]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Team not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // Delete team
    public function deleteTeam($team_id)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $this->teamService->deleteTeam($team_id, auth()->user()->user_id);

            return response()->json(['message' => 'Team deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Team not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode() === 403 ? 403 : 500);
        }
    }


    // Get team members
    public function getTeamMembers($team_id)
    {
        try {
            $members = $this->teamService->getTeamMembers($team_id);
            return response()->json($members);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Team not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to retrieve members', 'details' => $e->getMessage()], 500);
        }
    }
}
