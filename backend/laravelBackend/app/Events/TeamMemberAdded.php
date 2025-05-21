<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamMemberAdded 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;
    public $addedUserId;
    public $addedByUserId;

    public function __construct($team, $addedUserId, $addedByUserId)
    {
        // \Log::info("Event task member". $addedUserId ."". $addedByUserId ."");
        $this->team = $team;
        $this->addedUserId = $addedUserId;
        $this->addedByUserId = $addedByUserId;
    }
}
