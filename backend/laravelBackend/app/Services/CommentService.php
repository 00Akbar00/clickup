<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class CommentService
{
    protected $nodeServerUrl;

    public function __construct()
    {
        $this->nodeServerUrl = config('services.node.comment_url');
    }

    // app/Services/CommentService.php
    public function sendToNodeServer(array $data)
    {
        try {
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
    public function getCommentsFromNodeServer(string $taskId)
    {
        $response = Http::get("{$this->nodeServerUrl}/api/comments/{$taskId}");
        if ($response->successful()) {
            return response()->json($response->json(), 200);
        }

        return response()->json([
            'message' => 'Failed to fetch comments.',
            'error' => $response->body(),
        ], $response->status());
    }
    
    public function publishCommentUpdate(array $data)
    {
        Redis::publish('comments', json_encode($data));
        return response()->json(['message' => 'Comment update published'], 200);
    }
}