<?php

namespace App\Services\Project;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProjectService
{
    public function createProject(array $data, $team_id)
    {
        try {
            return Project::create([
                'project_id' => (string) Str::uuid(),
                'team_id' => $team_id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'color_code' => $data['color_code'] ?? null,
                'visibility' => $data['visibility'] ?? 'public',
                'status' => 'active',
                'created_by' => Auth::user()->user_id,
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to create project: ' . $e->getMessage());
        }
    }

    public function getProjects($team_id)
    {
        return Project::where('team_id', $team_id)->get();
    }

    public function getProjectDetails($project_id)
    {
        $project = Project::find($project_id);

        if (!$project) {
            throw new ModelNotFoundException('Project not found');
        }

        return $project;
    }

    public function updateProject(array $data, $project_id)
    {
        $project = Project::find($project_id);

        if (!$project) {
            throw new ModelNotFoundException('Project not found');
        }

        $project->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'color_code' => $data['color_code'] ?? null,
        ]);

        return $project;
    }

    public function deleteProject($project_id)
    {
        $project = Project::find($project_id);

        if (!$project) {
            throw new ModelNotFoundException('Project not found');
        }

        $project->delete();

        return true;
    }

    public function changeStatus($project_id, $status)
    {
        $project = Project::find($project_id);

        if (!$project) {
            throw new ModelNotFoundException('Project not found');
        }

        $project->status = $status;
        $project->save();

        return $project;
    }
}