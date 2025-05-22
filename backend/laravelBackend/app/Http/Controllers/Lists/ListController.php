<?php

namespace App\Http\Controllers\Lists;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateListRequest;
use App\Services\VerifyValidationService\ValidationService;
use App\Services\WorkspaceService\ListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class ListController extends Controller
{
    protected ListService $listService;

    public function __construct(ListService $listService)
    {
        $this->listService = $listService;
    }

    /**
     * Create a new list for a project.
     */

    public function createList(CreateListRequest $request, string $project_id): JsonResponse
    {
        try {
            $listData = $request->validated();

            $list = $this->listService->createList($project_id, $listData);

            return response()->json([
                'message' => 'List created successfully.',
                'list' => $list
            ], 201);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Get all lists for a specific project.
     */
    public function getLists(string $project_id): JsonResponse
    {
        try {
            $lists = $this->listService->getLists($project_id);

            return response()->json(['lists' => $lists], 200);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get details of a specific list.
     */
    public function getListDetails(string $list_id): JsonResponse
    {
        try {
            $list = $this->listService->getListsData($list_id);

            return response()->json(['list' => $list], 200);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update a list's details.
     */
    public function updateList(CreateListRequest $request, string $list_id): JsonResponse
    {
        $listData = $request->validated();

        try {
            
            $list = $this->listService->updateList($list_id, $listData);

            return response()->json([
                'message' => 'List updated successfully.',
                'list' => $list
            ], 200);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete a specific list.
     */
    public function deleteList(string $list_id): JsonResponse
    {
        try {
            $this->listService->deleteList($list_id);

            return response()->json(['message' => 'List deleted successfully.'], 200);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle exceptions and format error response.
     */
    protected function handleException(Exception $e): JsonResponse
    {
        $code = $e->getCode();
        $status = ($code >= 400 && $code < 600) ? $code : 500;

        return response()->json([
            'message' => 'An error occurred.',
            'error' => $e->getMessage()
        ], $status);
    }
}
