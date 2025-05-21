<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Models\Task;

class SendTaskAssignmentNotification
{
    public function handle(TaskAssigned $event)
    {
        $task = $event->task;
        $assignee = User::find($event->assigneeId);
        $assigner = User::find($event->assignedById);

        $notification = [
            'workspace_id' => $task->workspace_id,
            'recipient_id' => $assignee->user_id,
            'sender_id' => $assigner->user_id,
            'type' => 'task_assigned',
            'message' => "You've been assigned to task '{$task->title}' by {$assigner->full_name}",
            'related_entity' => [
                'type' => 'task',
                'id' => $task->task_id
            ],
            'created_at' => now()->toISOString()
        ];

        Redis::publish('notifications', json_encode($notification));
    }
}