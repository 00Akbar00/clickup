<?php

namespace App\Http\Controllers\Lists;
use App\Http\Controllers\Controller;


use App\Models\TaskList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ListController extends Controller
{
    // Create a new list 
    public function createList(Request $request,$project_id)
    {
        $request->validate([
            
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $list = TaskList::create([
            'list_id' => Str::uuid(),
            'project_id' => $project_id,
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => Auth::user()->user_id,
        ]);

        return response()->json(['message' => 'List created successfully', 'list' => $list], 201);
    }

    //Get Lists
    public function getLists($project_id)
    {
        $lists = TaskList::where('project_id', $project_id)
                         ->orderBy('position')
                         ->get();

        return response()->json(['lists' => $lists]);
    }
}
