<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUnassigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $assigneeId;
    public $unassignedById;

    public function __construct($task, $assigneeId, $unassignedById)
    {
        $this->task = $task;
        $this->assigneeId = $assigneeId;
        $this->unassignedById = $unassignedById;
    }
}
