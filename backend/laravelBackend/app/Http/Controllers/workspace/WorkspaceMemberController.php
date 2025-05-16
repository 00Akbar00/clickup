<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\User;

class WorkspaceMemberController extends Controller
{
    /**
     * Get all members of a workspace with their roles
     * 
     * @param Request $request
     * @param string $workspaceId (UUID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function listMembers(Request $request, $workspaceId)
    {
        try {
            // Validate workspace ID as UUID
            $validator = Validator::make(['workspace_id' => $workspaceId], [
                'workspace_id' => 'required|uuid|exists:workspaces,workspace_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if the authenticated user is a member of the workspace
            $isMember = WorkspaceMember::where('workspace_id', $workspaceId)
                ->where('user_id', Auth::id())
                ->exists();

            if (!$isMember) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not a member of this workspace'
                ], 403);
            }

            // Get all members with their user details and roles
            $members = WorkspaceMember::with('user')
                ->where('workspace_id', $workspaceId)
                ->get()
                ->map(function ($member) {
                    return [
                        'workspace_member_id' => $member->workspace_member_id,
                        'user_id' => $member->user_id,
                        'full_name' => $member->user->full_name,
                        'email' => $member->user->email,
                        'profile_picture_url' => $member->user->profile_picture_url,
                        'role' => $member->role,
                        'joined_at' => $member->joined_at,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $members
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch workspace members: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch workspace members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually add a user to a workspace
     * 
     * @param Request $request
     * @param string $workspaceId (UUID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMember(Request $request, $workspaceId)
    {
        try {
            // Validate input
            $validator = Validator::make(array_merge($request->all(), ['workspace_id' => $workspaceId]), [
                'workspace_id' => 'required|uuid|exists:workspaces,workspace_id',
                'email' => 'required|email|exists:users,email',
                'role' => 'required|in:owner,admin,member,guest',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get the workspace
            $workspace = Workspace::where('workspace_id', $workspaceId)->firstOrFail();

            // Check if the authenticated user has permission to add members
            $authMember = WorkspaceMember::where('workspace_id', $workspaceId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$authMember || !in_array($authMember->role, ['owner', 'admin'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to add members to this workspace'
                ], 403);
            }

            // Get the user to add
            $userToAdd = User::where('email', $request->email)->firstOrFail();

            // Check if user is already a member
            $existingMember = WorkspaceMember::where('workspace_id', $workspaceId)
                ->where('user_id', $userToAdd->user_id)
                ->exists();

            if ($existingMember) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User is already a member of this workspace'
                ], 409);
            }

            // Prevent adding a member with owner role if not current owner
            if ($request->role === 'owner' && $authMember->role !== 'owner') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only workspace owners can assign owner role'
                ], 403);
            }

            // Add the member
            $member = WorkspaceMember::create([
                'workspace_id' => $workspaceId,
                'user_id' => $userToAdd->user_id,
                'role' => $request->role,
                'joined_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Member added successfully',
                'data' => [
                    'workspace_member_id' => $member->workspace_member_id,
                    'user_id' => $member->user_id,
                    'full_name' => $userToAdd->full_name,
                    'email' => $userToAdd->email,
                    'role' => $member->role,
                    'joined_at' => $member->joined_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to add workspace member: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add workspace member',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}