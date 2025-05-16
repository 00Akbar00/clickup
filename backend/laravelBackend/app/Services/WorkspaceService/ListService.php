<?php

namespace App\Services\WorkspaceService;

use App\Models\TaskList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ListService
{
    public function createList($project_id, array $data)
    {
        try {
            return TaskList::create([
                'list_id' => Str::uuid(),
                'project_id' => $project_id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating list: ' . $e->getMessage());
            throw new \Exception('Failed to create list');
        }
    }

    public function getLists($project_id)
    {
        try {
            return TaskList::where('project_id', $project_id)->orderBy('position')->get();
        } catch (\Exception $e) {
            Log::error('Error fetching lists: ' . $e->getMessage());
            throw new \Exception('Failed to fetch lists');
        }
    }

    public function getListsData($list_id)
    {
        try {
            return TaskList::with(['project', 'creator'])->findOrFail($list_id);
        } catch (ModelNotFoundException $e) {
            throw new \Exception("List not found", 404);
        }
    }

    public function updateList($list_id, array $data)
    {
        try {
            $list = TaskList::findOrFail($list_id);
            $list->update($data);
            return $list;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('List not found');
        } catch (\Exception $e) {
            Log::error('Error updating list: ' . $e->getMessage());
            throw new \Exception('Failed to update list');
        }
    }

    public function deleteList($list_id)
    {
        try {
            $list = TaskList::findOrFail($list_id);
            $list->delete();
            return true;
        } catch (ModelNotFoundException $e) {
            throw new \Exception('List not found');
        } catch (\Exception $e) {
            Log::error('Error deleting list: ' . $e->getMessage());
            throw new \Exception('Failed to delete list');
        }
    }
}