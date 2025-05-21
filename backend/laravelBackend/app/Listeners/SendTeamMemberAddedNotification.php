<?php

namespace App\Listeners;

use App\Events\TeamMemberAdded;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Models\Team;

class SendTeamMemberAddedNotification
{
    public function handle(TeamMemberAdded $event)
    {
        $team = $event->team;
        $addedUser = User::find($event->addedUserId);
        $addedByUser = User::find($event->addedByUserId);

        $notification = [
            'workspace_id' => $team->workspace_id,
            'recipient_id' => $addedUser->user_id,
            'sender_id' => $addedByUser->user_id,
            'type' => 'team_member_added',
            'message' => "You've been added to team '{$team->name}' by {$addedByUser->full_name}",
            'related_entity' => [
                'type' => 'team',
                'id' => $team->team_id
            ],
            'created_at' => now()->toISOString()
        ];

        Redis::publish('notifications', json_encode($notification));
    }
}
