<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Task;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\TaskAssignee;
use App\Events\TaskAssigned;
use App\Events\TaskUnassigned;
use App\Services\VerifyValidationService\ValidationService;

class TaskAssigneeController extends Controller
{
    // Assign a user to a task
    public function assignTask(Request $request, $workspace_id, $task_id)
    {
        $rulesData = ValidationService::taskAssigneeRules();
        $validator = validator($request->all(), $rulesData['rules'], $rulesData['messages']);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $task = Task::with('list.project.team')->where('task_id', $task_id)->firstOrFail();
        $team = $task->list->project->team;
    
        if ($team->workspace_id !== $workspace_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Task does not belong to this workspace'
            ], 403);
        }
    
        // Get all valid team members at once
        $teamMemberIds = is_array($request->team_member_id) 
            ? $request->team_member_id 
            : [$request->team_member_id];
    
        $teamMembers = TeamMember::with('user')
            ->whereIn('team_member_id', $teamMemberIds)
            ->where('team_id', $team->team_id)
            ->get();
    
        if ($teamMembers->count() !== count($teamMemberIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'One or more team members not found in this team'
            ], 404);
        }
    
        // Check for existing assignments
        $existingAssignments = TaskAssignee::where('task_id', $task_id)
            ->whereIn('team_member_id', $teamMemberIds)
            ->pluck('team_member_id')
            ->toArray();
    
        if (!empty($existingAssignments)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some users are already assigned to this task',
                'existing_assignments' => $existingAssignments
            ], 409);
        }
    
        DB::beginTransaction();
        try {
            $assignments = [];
            foreach ($teamMembers as $teamMember) {
                $assignment = TaskAssignee::create([
                    'task_assignee_id' => Str::uuid(),
                    'task_id' => $task_id,
                    'team_member_id' => $teamMember->team_member_id,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                ]);
    
                event(new TaskAssigned(
                    $task,
                    $teamMember->user->user_id,
                    Auth::id(),
                ));
    
                $assignments[] = $assignment;
            }
    
            DB::commit();
    
            return response()->json([
                'status' => 'success',
                'message' => count($assignments) . ' users assigned to task successfully',
                'data' => $assignments
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign users to task',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function unassignTask($workspace_id, $task_id, $workspace_member_id)
    {
        try {
            $assignee = TaskAssignee::with(['workspaceMember.user', 'task'])
                ->where('task_id', $task_id)
                ->where('workspace_member_id', $workspace_member_id)
                ->firstOrFail();

            $task = $assignee->task;
            $userId = $assignee->workspaceMember->user->user_id;

            $assignee->delete();

            // Dispatch event
            event(new TaskUnassigned(
                $task,
                $userId,
                Auth::id()
            ));

            return response()->json(['message' => 'User unassigned successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unassignment failed', 'details' => $e->getMessage()], 500);
        }
    }

    // Get all users assigned to a task
    public function getTaskAssignees($task_id)
    {
        try {
            $taskAssignees = TaskAssignee::with('workspaceMember.user')
                ->where('task_id', $task_id)
                ->get();

            $assignees = $taskAssignees->map(function ($assignee) {
                return [
                    'workspace_member_id' => $assignee->workspace_member_id,
                    'user' => $assignee->workspaceMember->user ?? null,
                ];
            });

            return response()->json(['assignees' => $assignees]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch assignees', 'details' => $e->getMessage()], 500);
        }
    }


    public function getUserAssignedTasks($workspace_id)
    {
        $userId = Auth::id();

        $assignments = TaskAssignee::with(['task.listTask.project.team', 'teamMember.user'])
            ->whereHas('teamMember', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereHas('task.listTask.project.team', function ($query) use ($workspace_id) {
                $query->where('workspace_id', $workspace_id);
            })
            ->get()
            ->map(function ($assignment) {
                return [
                    'task_id' => $assignment->task->task_id,
                    'title' => $assignment->task->title,
                    'description' => $assignment->task->description,
                    'due_date' => $assignment->task->due_date,
                    'status' => $assignment->task->status,
                    'project' => $assignment->task->list->project->name,
                    'team' => $assignment->task->list->project->team->name,
                    'workspace_id' => $assignment->task->list->project->team->workspace_id,
                    'assigned_at' => $assignment->assigned_at,
                    'assigned_by' => $assignment->assigned_by,
                ];
            });

        return response()->json(['tasks' => $assignments]);
    }

}
