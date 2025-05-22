<?php

namespace App\Http\Controllers\Workspace;
use App\Http\Requests\CreateWorkspaceRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Intervention\Image\Encoders\PngEncoder;
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\WorkspaceMember;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TaskList;
use App\Models\User;
use Exception;


class WorkspaceController extends Controller
{
    // Create new workspace
    public function createWorkspace(CreateWorkspaceRequest $request)
    {
        try {
            DB::beginTransaction();

            $name = $request->input('name', 'Workspace');
            $workspaceId = Str::uuid();
            $avatarDirectory = 'workspace-logos';

            Storage::makeDirectory("public/{$avatarDirectory}");

            // Handle logo upload or avatar generation
            if ($request->hasFile('logo')) {
                $uploadedLogo = $request->file('logo');
                $avatarFileName = Str::uuid() . '.' . $uploadedLogo->getClientOriginalExtension();
                $avatarPath = "{$avatarDirectory}/{$avatarFileName}";

                Storage::disk('public')->putFileAs($avatarDirectory, $uploadedLogo, $avatarFileName);
            } else {
                $initial = strtoupper(mb_substr(trim($name), 0, 1));
                $avatar = Avatar::create($initial)->getImageObject()->encode(new PngEncoder());

                $avatarFileName = Str::uuid() . '.png';
                $avatarPath = "{$avatarDirectory}/{$avatarFileName}";

                Storage::disk('public')->put($avatarPath, $avatar);
            }

            $logoUrl = asset("storage/{$avatarPath}");

            // Create workspace record
            DB::table('workspaces')->insert([
                'workspace_id' => $workspaceId,
                'name' => $name,
                'description' => $request->input('description'),
                'created_by' => Auth::id(),
                'invite_token' => Str::uuid(),
                'invite_token_expires_at' => Carbon::now()->addDays(7),
                'logo_url' => $logoUrl,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Add user as owner to workspace_members
            DB::table('workspace_members')->insert([
                'workspace_member_id' => Str::uuid(),
                'workspace_id' => $workspaceId,
                'user_id' => Auth::id(),
                'role' => 'owner',
                'joined_at' => now()
            ]);

            // Update user status
            User::where('user_id', Auth::id())->update([
                'is_part_of_workspace' => true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Workspace created successfully',
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
        try {
            $userId = Auth::user()->user_id;

            // Get all workspace IDs for this user
            $workspaceIds = WorkspaceMember::where('user_id', $userId)
                ->pluck('workspace_id');

            // Now fetch workspaces using those IDs
            $workspaces = Workspace::whereIn('workspace_id', $workspaceIds)->get();

            return response()->json([
                'success' => true,
                'workspaces' => $workspaces,
                'message' => 'Workspaces fetched successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch workspaces.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getWorkspaceById(Request $request, $workspaceId)
    {
        try {
            $userId = Auth::user()->user_id;

            // Check if user is a member of the given workspace
            $isMember = WorkspaceMember::where('user_id', $userId)
                ->where('workspace_id', $workspaceId)
                ->exists();

            if (!$isMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this workspace.',
                ], 403);
            }

            // Fetch the workspace
            $workspace = Workspace::findOrFail($workspaceId);

            return response()->json([
                'success' => true,
                'workspace' => $workspace,
                'message' => 'Workspace retrieved successfully.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not found.',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while retrieving the workspace.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateWorkspace(Request $request, $workspace_id)
    {
        try {
            // Validation
            // $validator = Validator::make($request->all(), [
            //     'name' => [
            //         'required',
            //         'string',
            //         'max:255',
            //         'regex:/^[a-zA-Z0-9\s]+$/',
            //     ],
            //     'description' => 'nullable|string|max:100',
            //     'logo_url' => 'nullable|file|mimes:jpg,jpeg,png|max:1024',
            // ]);

            // if ($validator->fails()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Validation failed.',
            //         'errors' => $validator->errors()->toArray(),
            //     ], 422);
            // }

            // Workspace existence check
            $workspace = Workspace::find($workspace_id);
            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workspace not found.',
                ], 404);
            }

            // Authorization check
            $userId = Auth::user()->user_id;
            $isAuthorized = WorkspaceMember::where('workspace_id', $workspace_id)
                ->where('user_id', $userId)
                ->whereIn('role', ['owner', 'member'])
                ->exists();

            if (!$isAuthorized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.',
                ], 403);
            }

            // Prepare data
            $data = $request->only(['name', 'description']);

            // Handle logo
            if ($request->hasFile('logo_url')) {
                $logoFile = $request->file('logo_url');
                $fileName = uniqid('workspace_logo_') . '.' . $logoFile->getClientOriginalExtension();
                $filePath = 'workspace_logos/' . $fileName;

                // Delete old logo
                if ($workspace->logo_url) {
                    $oldPath = str_replace('/storage/', 'public/', $workspace->logo_url);
                    if (Storage::exists($oldPath)) {
                        Storage::delete($oldPath);
                    }
                }

                // Save new logo
                Storage::disk('public')->put($filePath, file_get_contents($logoFile));
                $data['logo_url'] = Storage::url($filePath);
            }

            // Update
            $workspace->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Workspace updated successfully.',
                'data' => [
                    'workspace' => $workspace,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while updating the workspace.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function deleteWorkspace($workspace_id)
    {
        try {
            // transaction
            DB::beginTransaction();

            // Fetch workspace
            $workspace = Workspace::find($workspace_id);

            if (!$workspace) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workspace not found.'
                ], 404);
            }

            // Authorization - Only owner can delete
            $userId = Auth::user()->user_id;

            $isOwner = WorkspaceMember::where('workspace_id', $workspace_id)
                ->where('user_id', $userId)
                ->where('role', 'owner')
                ->exists();

            if (!$isOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only the workspace owner can delete this workspace.'
                ], 403);
            }

            // Delete all associated data
            $teams = Team::where('workspace_id', $workspace_id)->get();

            foreach ($teams as $team) {
                $projects = Project::where('team_id', $team->team_id)->get();

                foreach ($projects as $project) {
                    $lists = TaskList::where('project_id', $project->project_id)->get();

                    foreach ($lists as $list) {
                        $tasks = Task::where('list_id', $list->list_id)->get();

                        foreach ($tasks as $task) {
                            TaskAssignee::where('task_id', $task->task_id)->delete();
                            $task->delete();
                        }

                        $list->delete();
                    }

                    $project->delete();
                }

                TeamMember::where('team_id', $team->team_id)->delete();
                $team->delete();
            }

            WorkspaceMember::where('workspace_id', $workspace_id)->delete();
            $workspace->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Workspace and all related data deleted successfully.'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();

            // Log error for debugging
            Log::error('Failed to delete workspace', [
                'workspace_id' => $workspace_id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the workspace.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

}