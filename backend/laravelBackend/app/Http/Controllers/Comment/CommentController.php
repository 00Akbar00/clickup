<?php
namespace App\Http\Controllers\Comment;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Services\CommentService;

class CommentController extends Controller
{
    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    // Store a new comment
    public function createComment(Request $request, $taskId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        try {
            $data = $request->validate([
                // 'sender_id' => 'required|uuid',
                'comment' => 'nullable|string',
                'files' => 'nullable|array',
                'files.*' => 'string',
            ]);
    
            $data['task_id'] = $taskId;
            $data['sender_id'] = auth()->id();
    
            return $this->commentService->sendToNodeServer($data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getComments($taskId)
    {
        return $this->commentService->getCommentsFromNodeServer($taskId);
    }
}