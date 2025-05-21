<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Task;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\TaskAssignee;
use App\Events\TaskAssigned;
use App\Events\TaskUnassigned;

class TaskAssigneeController extends Controller
{
    // Assign a user to a task
    public function assignTask(Request $request, $workspace_id, $task_id)
    {
        $request->validate([
            'team_member_id' => 'required|uuid|exists:team_members,team_member_id',
        ]);

        $task = Task::with('listTask.project.team')->where('task_id', $task_id)->firstOrFail();
        // Validate workspace via nested relation
        $team = $task->list->project->team;

        if ($team->workspace_id !== $workspace_id) {
            return response()->json(['error' => 'Task does not belong to this workspace'], 403);
        }

        // Ensure team member belongs to this team
        $teamMember = TeamMember::with('user')
            ->where('team_member_id', $request->team_member_id)
            ->where('team_id', $team->team_id)
            ->firstOrFail();
        // dd($teamMember);


        // Check if already assigned via team_member_id
        $alreadyAssigned = TaskAssignee::where('task_id', $task_id)
            ->where('team_member_id', $teamMember->team_member_id) // Use the actual value from teamMember
            ->exists();

        if ($alreadyAssigned) {
            return response()->json(['message' => 'User is already assigned to this task'], 409);
        }

        // Debug check
        if (empty($teamMember->team_member_id)) {
            return response()->json(['error' => 'Invalid team member ID'], 400);
        }

        // Create assignment using team_member_id
        $assignmentData = [
            'task_assignee_id' => Str::uuid(),
            'task_id' => $task_id,
            'team_member_id' => $teamMember->team_member_id,
            'assigned_by' => Auth::id(),
            'assigned_at' => now(),
        ];

        $taskAssignee = TaskAssignee::create($assignmentData);

        event(new TaskAssigned(
            $task,
            $teamMember->user->user_id,
            Auth::id(),
        ));
        return response()->json(['message' => 'User assigned to task successfully', 'task assigned' => $taskAssignee]);
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
