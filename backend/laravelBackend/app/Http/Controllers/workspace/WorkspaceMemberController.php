<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\User;
use App\Models\TeamMember;

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


    /**
     * Update a member's role in the workspace
     * 
     * @param Request $request
     * @param string $workspaceId (UUID)
     * @param string $memberId (UUID of workspace_member_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMemberRole(Request $request, $workspaceId, $memberId)
    {
        DB::beginTransaction();
        try {
            // Validate input
            $validator = Validator::make(array_merge($request->all(), [
                'workspace_id' => $workspaceId,
                'member_id' => $memberId
            ]), [
                'workspace_id' => 'required|uuid|exists:workspaces,workspace_id',
                'member_id' => 'required|uuid|exists:workspace_members,workspace_member_id',
                'role' => 'required|in:owner,member',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get the member to update
            $memberToUpdate = WorkspaceMember::where('workspace_member_id', $memberId)
                ->where('workspace_id', $workspaceId)
                ->firstOrFail();

            // Check auth user's permissions
            $authMember = WorkspaceMember::where('workspace_id', $workspaceId)
                ->where('user_id', Auth::id())
                ->first();

            // Only owners can change roles
            if (!$authMember || $authMember->role !== 'owner') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only workspace owners can change roles'
                ], 403);
            }

            // Prevent last owner demotion
            if ($memberToUpdate->role === 'owner' && $request->role === 'member') {
                $ownerCount = WorkspaceMember::where('workspace_id', $workspaceId)
                    ->where('role', 'owner')
                    ->count();

                if ($ownerCount <= 1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot demote last owner. Transfer ownership first or add another owner.'
                    ], 422);
                }
            }

            // Handle ownership transfer
            if ($request->role === 'owner' && $memberToUpdate->role !== 'owner') {
                // Demote current owner(s)
                WorkspaceMember::where('workspace_id', $workspaceId)
                    ->where('role', 'owner')
                    ->update(['role' => 'member']);
            }

            // Update the role
            $memberToUpdate->update(['role' => $request->role]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Role updated successfully',
                'data' => [
                    'member_id' => $memberId,
                    'new_role' => $request->role,
                    'previous_role' => $memberToUpdate->getOriginal('role')
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Role update failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a member from the workspace
     * 
     * @param Request $request
     * @param string $workspaceId (UUID)
     * @param string $memberId (UUID of workspace_member_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMember(Request $request, $workspaceId, $memberId)
    {
        try {
            // Validate input
            $validator = Validator::make([
                'workspace_id' => $workspaceId,
                'member_id' => $memberId
            ], [
                'workspace_id' => 'required|uuid|exists:workspaces,workspace_id',
                'member_id' => 'required|uuid|exists:workspace_members,workspace_member_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get the workspace member to remove
            $memberToRemove = WorkspaceMember::where('workspace_member_id', $memberId)
                ->where('workspace_id', $workspaceId)
                ->firstOrFail();

            // Check if the authenticated user has permission to remove members
            $authMember = WorkspaceMember::where('workspace_id', $workspaceId)
                ->where('user_id', Auth::id())
                ->first();

            // Only owners can remove members (admins can only remove non-owners)
            if (
                !$authMember ||
                ($authMember->role !== 'owner' && $memberToRemove->role === 'owner')
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to remove this member'
                ], 403);
            }

            // Prevent removing the last owner
            if ($memberToRemove->role === 'owner') {
                $ownerCount = WorkspaceMember::where('workspace_id', $workspaceId)
                    ->where('role', 'owner')
                    ->count();

                if ($ownerCount <= 1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Workspace must have at least one owner'
                    ], 422);
                }
            }

            // Remove the member from all teams first
            TeamMember::where('user_id', $memberToRemove->user_id)
                ->whereIn('team_id', function ($query) use ($workspaceId) {
                    $query->select('team_id')
                        ->from('teams')
                        ->where('workspace_id', $workspaceId);
                })
                ->delete();

            // Now delete the workspace member
            $memberToRemove->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Member removed successfully',
                'data' => [
                    'removed_member_id' => $memberId,
                    'removed_user_id' => $memberToRemove->user_id,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove workspace member: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Current user leaves the workspace
     * 
     * @param Request $request
     * @param string $workspaceId (UUID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function leaveWorkspace(Request $request, $workspaceId)
    {
        try {
            // Validate input
            $validator = Validator::make([
                'workspace_id' => $workspaceId,
            ], [
                'workspace_id' => 'required|uuid|exists:workspaces,workspace_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get the current user's membership
            $currentMember = WorkspaceMember::where('workspace_id', $workspaceId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Prevent the last owner from leaving
            if ($currentMember->role === 'owner') {
                $ownerCount = WorkspaceMember::where('workspace_id', $workspaceId)
                    ->where('role', 'owner')
                    ->count();

                if ($ownerCount <= 1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You cannot leave as the last owner. Transfer ownership first.'
                    ], 422);
                }
            }

            // Remove from all teams in this workspace
            TeamMember::where('user_id', Auth::id())
                ->whereIn('team_id', function ($query) use ($workspaceId) {
                    $query->select('team_id')
                        ->from('teams')
                        ->where('workspace_id', $workspaceId);
                })
                ->delete();

            // Remove workspace membership
            $currentMember->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'You have left the workspace successfully',
                'data' => [
                    'workspace_id' => $workspaceId,
                    'user_id' => Auth::id(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to leave workspace: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to leave workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
