<?php

namespace App\Listeners;

use App\Events\TaskUnassigned;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Models\Task;

class SendTaskUnassignmentNotification
{
    public function handle(TaskUnassigned $event)
    {
        $task = $event->task;
        $assignee = User::find($event->assigneeId);
        $unassigner = User::find($event->unassignedById);

        $notification = [
            'workspace_id' => $task->workspace_id,
            'recipient_id' => $assignee->user_id,
            'sender_id' => $unassigner->user_id,
            'type' => 'task_unassigned',
            'message' => "You've been unassigned from task '{$task->title}' by {$unassigner->name}",
            'related_entity' => [
                'type' => 'task',
                'id' => $task->task_id
            ],
            'created_at' => now()->toISOString()
        ];

        Redis::publish('notifications', json_encode($notification));
    }
}
