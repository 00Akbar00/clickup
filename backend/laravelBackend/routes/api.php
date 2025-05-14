<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SignupController;
use App\Http\Controllers\Lists\ListController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Teams\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Providers\RouteServiceProvider;

Route::middleware(['throttle:signup-limiter'])->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/signup', [SignupController::class, 'register']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post("forgot-password",[PasswordResetController::class,'forgotPassword']);
// Route::getdocke('reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm']);
Route::middleware('check.token.expiry')->group(function () {
    Route::post("reset-password/{token}",[PasswordResetController::class,'resetPassword']);
});



Route::prefix('teams')->middleware(['auth:api'])->group(function () {
    Route::post('/create/{workspace_id}', [TeamController::class, 'createTeam']);
    Route::get('/workspace/{workspace_id}', [TeamController::class, 'getTeams']);
    Route::get('/{team_id}', [TeamController::class, 'getTeamDetails']);
    Route::put('/{team_id}', [TeamController::class, 'updateTeam']);
    Route::delete('/{team_id}', [TeamController::class, 'deleteTeam']);
    Route::get('/{team_id}/members', [TeamController::class, 'getTeamMembers']);

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
    Route::put('/lists/{list_id}', [ListController::class, 'updateList']);
    Route::delete('/lists/{list_id}', [ListController::class, 'deleteList']);
    Route::patch('/lists/reorder', [ListController::class, 'reorderLists']);

});