<?php
namespace App\Services\Task;

use App\Models\Task;
use Illuminate\Support\Str;

class TaskService
{
    public function createTask(array $data, string $list_id, string $user_id)
    {
        return Task::create([
            'task_id' => (string) Str::uuid(),
            'list_id' => $list_id,
            'created_by' => $user_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'priority' => $data['priority'] ?? 'clear',
            'status' => $data['status'] ?? 'todo',

        ]);
    }

    public function getTasksByList(string $list_id)
    {
        return Task::where('list_id', $list_id)->get();
    }

    public function getTaskById(string $task_id)
    {
        // return Task::with(['creator', 'assignees'])->findOrFail($task_id);
        return Task::with(['creator'])->findOrFail($task_id);
    }

    public function updateTask(string $task_id, array $data)
    {
        $task = Task::findOrFail($task_id);
        $task->update($data);
        return $task;
    }

    public function deleteTask(string $task_id)
    {
        $task = Task::findOrFail($task_id);
        return $task->delete();
    }

    public function changeStatus(string $task_id, string $status)
    {
        $task = Task::findOrFail($task_id);
        $task->status = $status;
        if ($status === 'completed') {
            $task->completed_at = now();
        }
        $task->save();
        return $task;
    }
}
