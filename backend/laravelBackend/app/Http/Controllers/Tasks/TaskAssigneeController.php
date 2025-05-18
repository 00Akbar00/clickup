<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
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
            'workspace_member_id' => 'required|uuid|exists:workspace_members,workspace_member_id',
        ]);

        try {
            $member = WorkspaceMember::with('user')
                ->where('workspace_id', $workspace_id)
                ->where('workspace_member_id', $request->workspace_member_id)
                ->firstOrFail();

            $task = Task::findOrFail($task_id);

            $assignee = TaskAssignee::create([
                'task_assignee_id' => Str::uuid(),
                'task_id' => $task_id,
                'workspace_member_id' => $request->workspace_member_id,
                'assigned_by' => Auth::id(),
            ]);

            // Dispatch event
            event(new TaskAssigned(
                $task,
                $member->user->user_id,
                Auth::id()
            ));

            return response()->json(['message' => 'User assigned to task successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Assignment failed', 'details' => $e->getMessage()], 500);
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


    // Get all users in the workspace
    public function getWorkspaceUsers($workspace_id)
    {
        try {
            $workspace = Workspace::with('members.user')->findOrFail($workspace_id);

            $users = $workspace->members->map(function ($member) {
                return [
                    'workspace_member_id' => $member->workspace_member_id,
                    'user' => $member->user,
                ];
            });

            return response()->json(['users' => $users]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch users', 'details' => $e->getMessage()], 500);
        }
    }
}
