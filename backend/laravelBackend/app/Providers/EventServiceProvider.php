<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    protected $listen = [
        \App\Events\CommentCreated::class => [
            \App\Listeners\PublishCommentToNodeServer::class,
        ],
        \App\Events\TaskAssigned::class => [
            \App\Listeners\SendTaskAssignmentNotification::class,
        ],
        \App\Events\TaskUnassigned::class => [
            \App\Listeners\SendTaskUnassignmentNotification::class,
        ],
        \App\Events\TeamMemberAdded::class => [
            \App\Listeners\SendTeamMemberAddedNotification::class,
        ],
    ];
    
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
