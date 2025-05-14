<?php

namespace App\Http\Controllers\Projects;
use App\Http\Controllers\Controller;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    // Create a new project
    public function createProject(Request $request,$team_id)
    {
        $request->validate([
            
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'in:public,private',
            'color_code' => 'nullable|string|max:7',
        ]);

        $project = Project::create([
            'project_id' => (string) Str::uuid(),
            'team_id' => $team_id,
            'name' => $request->name,
            'description' => $request->description,
            'color_code' => $request->color_code,
            'visibility' => $request->visibility ?? 'private',
            'status' => 'active',
            'created_by' => Auth::user()->user_id,
        ]);

        return response()->json(['message' => 'Project created successfully', 'project' => $project], 201);
    }

    //Get Projects
    public function getProjects($team_id)
    {
        $projects = Project::where('team_id', $team_id)->get();

        return response()->json(['projects' => $projects]);
    }

    //Get Project Details
    public function getProjectDetails($project_id)
    {
        $project = Project::findOrFail($project_id);

        return response()->json(['project' => $project]);
    }

    //Update Project 
    public function updateProject(Request $request, $project_id)
    {
        $project = Project::findOrFail($project_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'in:public,private',
            'color_code' => 'nullable|string|max:7',
        ]);

        $project->update($request->only([
            'name', 'description', 'color_code'
        ]));

        return response()->json(['message' => 'Project updated successfully', 'project' => $project]);
    }

    //Delete Project 
    public function deleteProject($project_id)
    {
        $project = Project::findOrFail($project_id);
        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }
    public function changeProjectStatus(Request $request, $project_id)
    {
        $project = Project::findOrFail($project_id);

        $request->validate([
            'status' => 'required|in:active,archived',
        ]);

        $project->status = $request->status;
        $project->save();

        return response()->json(['message' => 'Project status updated', 'project' => $project]);
    }
}