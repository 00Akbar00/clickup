<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProjectRequest;
use App\Services\VerifyValidationService\ValidationService;
use App\Services\WorkspaceService\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function createProject(CreateProjectRequest $request, $team_id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $validator = $request->validated();
    
            $project = $this->projectService->createProject($validator, $team_id);
            return response()->json(['message' => 'Project created successfully', 'project' => $project], 201);

        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation error', 'messages' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to create project', 'details' => $e->getMessage()], 500);
        }
    }

    public function getProjects($team_id)
    {
        try {
            $projects = $this->projectService->getProjects($team_id);
            return response()->json(['projects' => $projects]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to retrieve projects', 'details' => $e->getMessage()], 500);
        }
    }

    public function getProjectDetails($project_id)
    {
        try {
            $project = $this->projectService->getProjectDetails($project_id);
            return response()->json(['project' => $project]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Project not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to get project details', 'details' => $e->getMessage()], 500);
        }
    }

    public function updateProject(Request $request, $project_id)
    {
        try {

            $validated = $request->validate([
                'name' => ValidationService::nameRules(),
                'description' => ValidationService::descriptionRules(),
                'visibility' => 'in:public,private',
                'color_code' => 'nullable|string|max:7',
            ]);

            $project = $this->projectService->updateProject($validated, $project_id);

            return response()->json([
                'message' => 'Project updated successfully',
                'project' => $project
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Project not found'], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation error',
                'messages' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Failed to update project',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function deleteProject($project_id)
    {
        try {
            $this->projectService->deleteProject($project_id);
            return response()->json(['message' => 'Project deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Project not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to delete project', 'details' => $e->getMessage()], 500);
        }
    }

    public function changeProjectStatus(Request $request, $project_id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:active,archived',
            ]);

            $project = $this->projectService->changeStatus($project_id, $validated['status']);
            return response()->json(['message' => 'Project status updated', 'project' => $project]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Project not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation error', 'messages' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to update project status', 'details' => $e->getMessage()], 500);
        }
    }
}
