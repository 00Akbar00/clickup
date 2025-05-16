<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Services\CommentService\CommentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Redis;

class PublishCommentToNodeServer implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    public function handle(CommentCreated $event)
    {
        \Log::info("Listener", [""=> $event->commentData]);
        $this->commentService->sendToNodeServer($event->commentData);
    }
}
