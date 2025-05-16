<?php
namespace App\Services\CommentService;

use Illuminate\Support\Facades\Redis;

class CommentService
{
    protected $nodeServerUrl;

    public function __construct()
    {
        $this->nodeServerUrl = config('services.node.comment_url');
    }

    public function sendToNodeServer(array $data)
    {
        try {
            \Log::info('$data', $data);
            Redis::publish('comments', json_encode($data));
            return response()->json([
                'message' => 'Comment published for real-time processing',
                'data' => $data
            ], 202);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'node_url' => $this->nodeServerUrl
            ], 500);
        }
    }


    public function getCommentsFromNodeServer($taskId)
    {
        try {
            $channel = "comments_get:{$taskId}";
            $requestChannel = "get_comments:{$taskId}";
            $response = null;
            $timeout = now()->addSeconds(5);
    
            Redis::publish($requestChannel, json_encode(['task_id' => $taskId]));
    
            Redis::connection()->subscribe([$channel], function ($message) use (&$response, $channel, $timeout) {
                $response = json_decode($message, true);
                Redis::connection()->unsubscribe([$channel]); // unsubscribe after getting message
            });
    
            // Wait for response (up to 5 seconds)
            while (!$response && now()->lt(date: $timeout)) {
                usleep(100000); // sleep 0.1 second
            }
    
            if (!$response) {
                return response()->json(['error' => 'Timeout waiting for comment response'], 504);
            }
            return $response['comments'] = is_array($response['comments']) ? $response['comments'] : [];
    
        } catch (\Exception $e) {
            \Log::error("❌ Redis error: ".$e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function publishCommentUpdate(array $data)
    {
        Redis::publish('comments', json_encode($data));
        return response()->json(['message' => 'Comment update published'], 200);
    }
}