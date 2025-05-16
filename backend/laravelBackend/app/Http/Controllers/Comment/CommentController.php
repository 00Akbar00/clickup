<?php
namespace App\Http\Controllers\Comment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CommentService\CommentFileService;
use App\Events\CommentCreated;
use App\Services\CommentService\CommentService;
use Illuminate\Support\Facades\Event;

class CommentController extends Controller
{
    protected $commentService;
    protected $fileService;

    public function __construct(CommentService $commentService, CommentFileService $fileService)
    {
        $this->commentService = $commentService;
        $this->fileService = $fileService;
    }

    public function createComment(Request $request, $taskId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $data = $request->validate([
                'comment' => 'nullable|string',
                'files.*' => 'file|max:10240',
                'files' => 'nullable',
            ]);

            $filesInfo = [];

            if ($request->hasFile('files')) {
                $filesInfo = $this->fileService->uploadFiles($request->file('files'));
            }

            $commentData = [
                'task_id'   => $taskId,
                'sender_id' => auth()->id(),
                'comment'   => $request->input('comment'),
                'files'     => $filesInfo,
                'timestamp' => now()->toISOString(),
            ];

            // Fire event
            Event::dispatch(new CommentCreated($commentData));
    

            return response()->json(['message' => 'Comment sent successfully',$commentData], 200);

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
