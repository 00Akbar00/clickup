<?php
namespace App\Http\Controllers\Lists;

use App\Http\Controllers\Controller;
use App\Services\ListService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class ListController extends Controller
{
    protected $listService;

    public function __construct(ListService $listService)
    {
        $this->listService = $listService;
    }

    // Create a new list 
    public function createList(Request $request, $project_id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $list = $this->listService->createList($project_id, $request->all());
            return response()->json(['message' => 'List created successfully', 'list' => $list], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //Get lists 
    public function getLists($project_id)
    {
        try {
            $lists = $this->listService->getLists($project_id);
            return response()->json(['lists' => $lists]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get list details 
    public function getListDetails($list_id)
    {
        try {
            $list = $this->listService->getListsData($list_id);
            return response()->json(['list' => $list], 200);
        } catch (Exception $e) {
            $status = $e->getCode() ?: 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }

    // Update list 
    public function updateList(Request $request, $list_id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,archived',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $list = $this->listService->updateList($list_id, $request->only(['name', 'description', 'status']));
            return response()->json(['message' => 'List updated successfully', 'list' => $list]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //Delete list
    public function deleteList($list_id)
    {
        try {
            $this->listService->deleteList($list_id);
            return response()->json(['message' => 'List deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
