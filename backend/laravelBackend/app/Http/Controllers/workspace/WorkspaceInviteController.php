<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
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
use App\Mail\WorkspaceInvitation;

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

            $workspace = Workspace::where('workspace_id', $workspaceId)->first();
            $this->refreshInviteToken($workspace);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'invite_link' => $this->buildInviteLink($workspace->invite_token),
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
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'emails' => 'required|array|min:1',
                'emails.*' => 'required|email',
                'role' => 'required|in:member,admin'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $this->authorizeInviteAction($workspaceId);
            $workspace = Workspace::where('workspace_id', $workspaceId)->first();

            if (!$workspace->isInviteValid()) {
                $this->refreshInviteToken($workspace);
            }

            $results = $this->sendBulkInvites(
                $workspace,
                $request->emails,
                $request->role,
                Auth::user()->full_name
            );

            DB::commit();
            return response()->json([
                'status' => 'success',
                'data' => [
                    'results' => $results,
                    'expires_at' => $workspace->invite_token_expires_at
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleError($e, 'Failed to send invitations');
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
    public function joinWorkspace(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate(['token' => 'required|string']);
            $workspace = $this->getValidWorkspace($request->token);

            if ($this->isAlreadyMember($workspace->workspace_id, Auth::id())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are already a member of this workspace'
                ], 409);
            }

            $this->addWorkspaceMember($workspace->workspace_id, Auth::id(), 'member');

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

    private function refreshInviteToken($workspace)
    {
        $workspace->update([
            'invite_token' => Str::orderedUuid(),
            'invite_token_expires_at' => Carbon::now()->addDays(self::INVITE_EXPIRY_DAYS)
        ]);
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
                if ($this->isAlreadyMember($workspace->workspace_id, $email)) {
                    $results[$email] = 'Already a member';
                    continue;
                }

                Mail::to($email)->queue(new WorkspaceInvitation(
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
