<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SignupController;
use App\Http\Controllers\Comment\CommentController;
use App\Http\Controllers\Lists\ListController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Tasks\TaskAssigneeController;
use App\Http\Controllers\Tasks\TaskController;
use App\Http\Controllers\Teams\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Providers\RouteServiceProvider;
use App\Http\Controllers\Workspace\WorkspaceController;

Route::middleware(['throttle:signup-limiter'])->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/signup', [SignupController::class, 'register']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post("forgot-password", [PasswordResetController::class, 'forgotPassword']);
// Route::getdocke('reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm']);
Route::middleware('check.token.expiry')->group(function () {
    Route::post("reset-password/{token}", [PasswordResetController::class, 'resetPassword']);
});

//Workspace routes
Route::middleware('auth:api')->group(function () {
    Route::prefix('workspaces')->group(function () {
        Route::get('/', [WorkspaceController::class, 'getWorkspaces']);
        Route::get('/{workspace_id}', [WorkspaceController::class, 'getWorkspace']);
        Route::put('/{workspace_id}', [WorkspaceController::class, 'updateWorkspace']);
        Route::post('/', [WorkspaceController::class, 'createWorkspace']);
        Route::delete('/{workspace_id}', [WorkspaceController::class, 'deleteWorkspace']);
    });

    Route::post('/invite/{token}', [WorkspaceController::class, 'processInvite']);
    Route::get('/invite/pending', [WorkspaceController::class, 'checkPendingInvites']);
});

Route::prefix('teams')->middleware(['auth:api'])->group(function () {
    Route::post('/create/{workspace_id}', [TeamController::class, 'createTeam']);
    Route::get('/workspace/{workspace_id}', [TeamController::class, 'getTeams']);
    Route::get('/{team_id}', [TeamController::class, 'getTeamDetails']);
    Route::put('/{team_id}', [TeamController::class, 'updateTeam']);
    Route::delete('/{team_id}', [TeamController::class, 'deleteTeam']);
    Route::get('/{team_id}/members', [TeamController::class, 'getTeamMembers']);
});
Route::middleware(['auth:api'])->group(function () {
    Route::post('/teams/create', [TeamController::class, 'createTeam']);
    Route::get('/user/profile', [UserController::class, 'getProfile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/profile-picture', [UserController::class, 'updateProfilePicture']);
});

Route::middleware('auth:api')->group(function () {
    Route::post('/projects/{team_id}', [ProjectController::class, 'createProject']);
    Route::get('/teams/{team_id}/projects', [ProjectController::class, 'getProjects']);
    Route::get('/projects/{project_id}', [ProjectController::class, 'getProjectDetails']);
    Route::put('/projects/{project_id}', [ProjectController::class, 'updateProject']);
    Route::delete('/projects/{project_id}', [ProjectController::class, 'deleteProject']);
    Route::patch('/projects/{project_id}/status', [ProjectController::class, 'changeProjectStatus']);
});

Route::middleware('auth:api')->group(function () {
    Route::post('/lists/{project_id}', [ListController::class, 'createList']);
    Route::get('/projects/{project_id}/lists', [ListController::class, 'getLists']);
    Route::get('lists/{list_id}', [ListController::class, 'getListDetails']);
    Route::put('/lists/{list_id}', [ListController::class, 'updateList']);
    Route::delete('/lists/{list_id}', [ListController::class, 'deleteList']);

});


Route::middleware('auth:api')->group(function () {
    Route::post('/tasks/{list_id}', [TaskController::class, 'createTask']);
    Route::get('/lists/{list_id}/tasks', [TaskController::class, 'getTasks']);
    Route::get('/tasks/{task_id}', [TaskController::class, 'getTaskDetails']);
    Route::put('/tasks/{task_id}', [TaskController::class, 'updateTask']);
    Route::delete('/tasks/{task_id}', [TaskController::class, 'deleteTask']);
    Route::patch('/tasks/{task_id}/status', [TaskController::class, 'changeTaskStatus']);
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('workspace/{workspace_id}')->group(function () {
        Route::get('/members', [TaskAssigneeController::class, 'getWorkspaceUsers']);
        Route::post('/tasks/{task_id}/assign', [TaskAssigneeController::class, 'assignTask']);
        Route::delete('/tasks/{task_id}/unassign/{workspace_member_id}', [TaskAssigneeController::class, 'unassignTask']);
    });

    Route::get('/tasks/{task_id}/assignees', [TaskAssigneeController::class, 'getTaskAssignees']);
});

Route::middleware('auth:api')->group(function () {
Route::post('/tasks/{task_id}/comments', [CommentController::class, 'createComment']);
Route::get('/tasks/{task_id}/comments', [CommentController::class, 'getComments']);
});
