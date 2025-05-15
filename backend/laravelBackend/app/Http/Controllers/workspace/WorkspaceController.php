<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TaskList;

class WorkspaceController extends Controller
{
    // Create new workspace
    public function createWorkspace(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Generate random background color
            $randomColor = sprintf('#%02X%02X%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));

            // Generate avatar
            $avatar = Avatar::create($request->name)
                ->setBackground($randomColor)
                ->setDimension(200)
                ->setFontSize(100);

            $avatarDirectory = 'workspace-logos';
            Storage::makeDirectory("public/{$avatarDirectory}");

            $avatarFileName = Str::uuid() . '.png';
            $avatarPath = "{$avatarDirectory}/{$avatarFileName}";
            $avatar->save(storage_path("app/public/{$avatarPath}"));

            // Create workspace with UUID
            $workspaceId = Str::uuid();

            DB::table('workspaces')->insert([
                'workspace_id' => $workspaceId,
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => Auth::user()->user_id,
                'invite_token' => Str::uuid(),
                'invite_token_expires_at' => Carbon::now()->addDays(7),
                'logo_url' => asset("storage/{$avatarPath}"),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Add creater to workspace_members with UUID
            DB::table('workspace_members')->insert([
                'workspace_member_id' => Str::uuid(),
                'workspace_id' => $workspaceId,
                'user_id' => Auth::user()->user_id,
                'role' => 'owner',
                'joined_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'workspace' => DB::table('workspaces')->where('workspace_id', $workspaceId)->first()
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Workspace creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Workspace creation failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    // List user's workspaces
    public function getWorkspaces()
    {
        $workspaces = DB::table('workspaces')
            ->join('workspace_members', 'workspaces.workspace_id', '=', 'workspace_members.workspace_id')
            ->where('workspace_members.user_id', Auth::user()->user_id)
            ->select('workspaces.*')
            ->get();

        return response()->json([
            'success' => true,
            'workspaces' => $workspaces
        ]);
    }

    public function getWorkspace($workspace_id)
    {
        try {
            $workspace = Workspace::find($workspace_id);

            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workspace not found'
                ], 404);
            }

            // Check if user is a member of the workspace
            $isMember = WorkspaceMember::where('workspace_id', $workspace_id)
                ->where('user_id', Auth::user()->user_id)
                ->exists();

            if (!$isMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - You are not a member of this workspace'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'workspace' => $workspace
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateWorkspace(Request $request, $workspace_id)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if workspace exists and user has permission to update it
            $workspace = Workspace::find($workspace_id);
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workspace not found'
                ], 404);
            }

            // Check if user is a member of the workspace with appropriate permissions
            $userMembership = WorkspaceMember::where('workspace_id', $workspace_id)
                ->where('user_id', Auth::user()->user_id)
                ->whereIn('role', ['owner', 'admin'])
                ->first();

            if (!$userMembership) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - You do not have permission to update this workspace'
                ], 403);
            }

            // Handle logo upload if present
            $logoUrl = $workspace->logo_url;
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($logoUrl && Storage::exists($logoUrl)) {
                    Storage::delete($logoUrl);
                }

                $path = $request->file('logo')->store('workspace_logos', 'public');
                $logoUrl = Storage::url($path);
            }

            // Update workspace data
            $updateData = [];
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }
            if ($logoUrl) {
                $updateData['logo_url'] = $logoUrl;
            }

            $workspace->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Workspace updated successfully',
                'workspace' => $workspace
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteWorkspace($workspace_id)
    {
        try {
            DB::beginTransaction();

            // Check if workspace exists
            $workspace = Workspace::find($workspace_id);
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workspace not found'
                ], 404);
            }

            // Check if user is the owner of the workspace
            $isOwner = WorkspaceMember::where('workspace_id', $workspace_id)
                ->where('user_id', Auth::user()->user_id)
                ->where('role', 'owner')
                ->exists();

            if (!$isOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Only workspace owners can delete the workspace'
                ], 403);
            }

            // Get all teams in the workspace
            $teams = Team::where('workspace_id', $workspace_id)->get();

            foreach ($teams as $team) {
                // Get all projects in the team
                $projects = Project::where('team_id', $team->team_id)->get();

                foreach ($projects as $project) {
                    // Get all lists in the project
                    $lists = TaskList::where('project_id', $project->project_id)->get();

                    foreach ($lists as $list) {
                        // Get all tasks in the list
                        $tasks = Task::where('list_id', $list->list_id)->get();

                        foreach ($tasks as $task) {
                            // Delete task assignees
                            TaskAssignee::where('task_id', $task->task_id)->delete();
                            // Delete the task
                            $task->delete();
                        }

                        // Delete the list
                        $list->delete();
                    }

                    // Delete the project
                    $project->delete();
                }

                // Delete team members
                TeamMember::where('team_id', $team->team_id)->delete();
                // Delete the team
                $team->delete();
            }

            // Delete workspace members
            WorkspaceMember::where('workspace_id', $workspace_id)->delete();

            // Delete the workspace itself
            $workspace->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Workspace and all associated data deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Workspace deletion error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete workspace',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

}
