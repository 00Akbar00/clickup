<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Jobs\SendWorkspaceInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Mail\WorkspaceInvitationMail;

class WorkspaceInviteController extends Controller
{
    const INVITE_EXPIRY_DAYS = 7;

    /**
     * Generate new invite token for workspace
     */
    public function generateInviteLink(Request $request, $workspaceId)
    {
        try {
            $this->validateWorkspace($workspaceId);
            $this->authorizeInviteAction($workspaceId);

            $workspace = Workspace::where('workspace_id', $workspaceId)->firstOrFail();

            // Generate and save invite token if missing or expired
            if (!$workspace->invite_token || now()->gt($workspace->invite_token_expires_at)) {
                $workspace->invite_token = Str::random(32);
                $workspace->invite_token_expires_at = now()->addHours(48);
                $workspace->save();
            }

            $inviteLink = $this->buildInviteLink($workspace->invite_token);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'invite_link' => $inviteLink,
                    'expires_at' => $workspace->invite_token_expires_at
                ]
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Failed to generate invite link');
        }
    }

    /**
     * Send email invitations to multiple users
     */

    public function sendInvitations(Request $request, $workspaceId)
    {
        Log::info("Sending workspace invitations...");

        $validator = Validator::make($request->all(), [
            'emails' => 'required|array|min:1',
            'emails.*' => 'required|email',
            'role' => 'required|in:member,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $emails = array_unique($request->emails);
        $role = $request->role;

        DB::beginTransaction();

        try {
            $workspace = Workspace::where('workspace_id', $workspaceId)->firstOrFail();

            if (!$workspace->invite_token || now()->gt($workspace->invite_token_expires_at)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invite token missing or expired. Please generate invite link first.',
                ], 400);
            }
 
            $inviterName = Auth::user()->full_name;
            $inviteLink = $this->buildInviteLink($workspace->invite_token);

            $results = [];

            foreach ($emails as $email) {
                try {
                    $invitation = WorkspaceInvitation::updateOrCreate(
                        ['workspace_id' => $workspace->workspace_id, 'email' => $email],
                        [
                            'invitation_id' => (string) Str::uuid(),
                            'role' => $role,
                            'inviter_name' => $inviterName,
                            'invite_token' => $workspace->invite_token,
                            'expires_at' => $workspace->invite_token_expires_at,
                            'status' => 'pending',
                        ]
                    );

                    SendWorkspaceInvitation::dispatch(
                        $email,
                        $workspace->name,
                        $inviterName,
                        $inviteLink,
                        $role,
                        $workspace->invite_token_expires_at
                    );

                    $results[$email] = 'invited';
                } catch (\Exception $e) {
                    Log::error("Failed to send invite to $email: " . $e->getMessage());
                    $results[$email] = 'error: ' . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'results' => $results,
                'invite_link' => $inviteLink,
                'expires_at' => $workspace->invite_token_expires_at,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invitation send failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send invitations',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Validate invite token (Public endpoint)
     */
    public function verifyInvite(Request $request)
    {
        try {
            $request->validate(['token' => 'required|string']);

            $workspace = $this->getValidWorkspace($request->token);
            $userStatus = $this->checkUserStatus($workspace);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'workspace' => [
                        'id' => $workspace->workspace_id,
                        'name' => $workspace->name
                    ],
                    'user_status' => $userStatus,
                    'expires_at' => $workspace->invite_token_expires_at
                ]
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Invalid invitation', 404);
        }
    }

    /**
     * Accept invitation (Auth required)
     */
    public function joinWorkspace(Request $request,$token)
    {
        DB::beginTransaction();

        try {
            // $request->validate([
            //     'token' => 'required|string'
            // ]);

            // Validate token and get workspace
            $workspace = $this->getValidWorkspace($token);

            // Check if already a member
            if ($this->isAlreadyMember($workspace->workspace_id, Auth::user()->user_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are already a member of this workspace'
                ], 409);
            }

            // Add to workspace members
            $this->addWorkspaceMember($workspace->workspace_id, Auth::user()->user_id, 'member');

            // ✅ Update user's is_part_of_workspace flag
            User::where('user_id', Auth::user()->user_id)->update(['is_part_of_workspace' => true]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'workspace' => [
                        'id' => $workspace->workspace_id,
                        'name' => $workspace->name
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleError($e, 'Failed to join workspace');
        }
    }

    /**
     * Revoke active invite link
     */
    public function revokeInvite(Request $request, $workspaceId)
    {
        try {
            $this->validateWorkspace($workspaceId);
            $this->authorizeInviteAction($workspaceId);

            Workspace::where('workspace_id', $workspaceId)
                ->update([
                    'invite_token' => null,
                    'invite_token_expires_at' => null
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Invite link revoked'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Failed to revoke invite');
        }
    }

    /***********************
     * Helper Methods *
     ***********************/

    private function validateWorkspace($workspaceId)
    {
        Validator::make(['workspace_id' => $workspaceId], [
            'workspace_id' => 'required|uuid|exists:workspaces,workspace_id'
        ])->validate();
    }

    private function authorizeInviteAction($workspaceId)
    {
        $isAuthorized = WorkspaceMember::where('workspace_id', $workspaceId)
            ->where('user_id', Auth::id())
            ->whereIn('role', ['owner', 'admin'])
            ->exists();

        if (!$isAuthorized) {
            abort(403, 'You need owner/admin privileges for this action');
        }
    }


    private function buildInviteLink($token)
    {
        return config('app.frontend_url') . '/join?token=' . $token;
    }

    private function sendBulkInvites($workspace, $emails, $role, $inviterName)
    {
        $results = [];
        $inviteLink = $this->buildInviteLink($workspace->invite_token);

        foreach ($emails as $email) {
            try {
                // Check if user is already a member
                if ($this->isAlreadyMember($workspace->workspace_id, $email)) {
                    $results[$email] = 'Already a member';
                    continue;
                }

                // Save or update invitation record in DB
                WorkspaceInvitation::updateOrCreate(
                    ['workspace_id' => $workspace->workspace_id, 'email' => $email],
                    [
                        'role' => $role,
                        'inviter_name' => $inviterName,
                        'invite_token' => $workspace->invite_token,
                        'expires_at' => $workspace->invite_token_expires_at,
                        'status' => 'pending',
                    ]
                );

                // Queue email
                Mail::to($email)->queue(new WorkspaceInvitationMail(
                    $workspace->name,
                    $inviterName,
                    $inviteLink,
                    $role,
                    $workspace->invite_token_expires_at
                ));

                $results[$email] = 'Invite sent';
            } catch (\Exception $e) {
                $results[$email] = 'Failed: ' . $e->getMessage();
                Log::error("Invite failed for {$email}: " . $e->getMessage());
            }
        }

        return $results;
    }


    private function getValidWorkspace($token)
    {
        $workspace = Workspace::where('invite_token', $token)
            ->where('invite_token_expires_at', '>', now())
            ->firstOrFail();

        return $workspace;
    }

    private function checkUserStatus($workspace)
    {
        if (Auth::check()) {
            return $this->isAlreadyMember($workspace->workspace_id, Auth::id())
                ? 'existing_member'
                : 'authenticated';
        }
        return 'unauthenticated';
    }

    private function isAlreadyMember($workspaceId, $userIdentifier)
    {
        $query = WorkspaceMember::where('workspace_id', $workspaceId);

        if (is_string($userIdentifier) && filter_var($userIdentifier, FILTER_VALIDATE_EMAIL)) {
            $query->whereHas('user', fn($q) => $q->where('email', $userIdentifier));
        } else {
            $query->where('user_id', $userIdentifier);
        }

        return $query->exists();
    }

    private function addWorkspaceMember($workspaceId, $userId, $role)
    {
        WorkspaceMember::create([
            'workspace_member_id' => (string) Str::uuid(),
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'role' => $role,
            'joined_at' => now()
        ]);
    }

    private function handleError(\Exception $e, $message = 'An error occurred', $code = 500)
    {
        Log::error($message . ': ' . $e->getMessage());

        return response()->json([
            'status' => 'error',
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : null
        ], $code);
    }
}
