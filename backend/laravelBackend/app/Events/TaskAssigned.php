<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $assigneeId;
    public $assignedById;

    public function __construct($task, $assigneeId, $assignedById)
    {
        $this->task = $task;
        $this->assigneeId = $assigneeId;
        $this->assignedById = $assignedById;
    }
}
