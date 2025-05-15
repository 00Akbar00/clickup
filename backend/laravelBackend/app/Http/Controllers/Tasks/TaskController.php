<?php
namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function createTask(Request $request, $list_id)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:high,normal,low,clear',
            'status' => 'nullable|in:todo,inprogress,completed',
        ]);

        try {
            $task = $this->taskService->createTask($request->all(), $list_id,auth()->id());
            return response()->json(['message' => 'Task created', 'task' => $task], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create task', 'details' => $e->getMessage()], 500);
        }
    }

    public function getTasks($list_id)
    {
        try {
            $tasks = $this->taskService->getTasksByList($list_id);
            return response()->json(['tasks' => $tasks]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch tasks'], 500);
        }
    }

    public function getTaskDetails($task_id)
    {
        try {
            $task = $this->taskService->getTaskById($task_id);
            return response()->json(['task' => $task]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        }
    }

    public function updateTask(Request $request, $task_id)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:high,normal,low,clear',
            'status' => 'nullable|in:todo,inprogress,completed',
        ]);

        try {
            $task = $this->taskService->updateTask($task_id, $request->all());
            return response()->json(['message' => 'Task updated', 'task' => $task]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        }
    }

    public function deleteTask($task_id)
    {
        try {
            $this->taskService->deleteTask($task_id);
            return response()->json(['message' => 'Task deleted']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        }
    }

    public function changeTaskStatus(Request $request, $task_id)
    {
        $request->validate([
            'status' => 'required|in:todo,inprogress,completed',
        ]);

        try {
            $task = $this->taskService->changeStatus($task_id, $request->status);
            return response()->json(['message' => 'Status updated', 'task' => $task]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        }
    }
}
