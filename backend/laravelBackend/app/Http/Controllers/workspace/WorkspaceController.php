<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WorkspaceController extends Controller
{
    // Create new workspace
    public function createWorkspace(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Generate workspace avatar
            $avatar = Avatar::create($request->name)
                ->setBackground('#FFFFFF')
                ->setDimension(200)
                ->setFontSize(100);

            $avatarPath = 'workspace-logos/' . Str::uuid() . '.png';
            $avatar->save(storage_path('app/public/' . $avatarPath));

            $workspaceId = Str::uuid(); // Generate UUID for workspace

            // Create workspace
            DB::table('workspaces')->insert([
                'workspace_id' => $workspaceId,
                'name' => $request->name,
                'description' => $request->description,
                'created_by' =>  Auth::user()->user_id,
                'invite_token' => Str::uuid(),
                'invite_token_expires_at' => Carbon::now()->addDays(7),
                'logo_url' => asset('storage/' . $avatarPath),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Add creator as owner
            DB::table('workspace_members')->insert([
                'workspace_id' => $workspaceId,
                'user_id' =>  Auth::user()->user_id,
                'role' => 'owner',
                'joined_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'workspace' => DB::table('workspaces')->where('workspace_id', $workspaceId)->first()
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log the error (optional but good for debugging later)
            Log::error('Workspace creation failed: ' . $e->getMessage());

            // Return the error in the response
            return response()->json([
                'success' => false,
                'message' => 'Workspace creation failed',
                'error' => $e->getMessage() // this sends the exception message to Postman
            ], 500);
        }
    }

    // List user's workspaces
    public function getWorkspaces()
    {
        $workspaces = DB::table('workspaces')
            ->join('workspace_members', 'workspaces.workspace_id', '=', 'workspace_members.workspace_id')
            ->where('workspace_members.user_id',  Auth::user()->user_id)
            ->select('workspaces.*')
            ->get();

        return response()->json([
            'success' => true,
            'workspaces' => $workspaces
        ]);
    }

    // Generate new invite token
    public function generateInviteToken($workspaceId)
    {
        $userId =  Auth::user()->user_id;

        // Verify user has permission
        $isAdmin = DB::table('workspace_members')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();

        if (!$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $token = Str::uuid();
        $expiresAt = Carbon::now()->addDays(7);

        DB::table('workspaces')
            ->where('workspace_id', $workspaceId)
            ->update([
                'invite_token' => $token,
                'invite_token_expires_at' => $expiresAt
            ]);

        return response()->json([
            'success' => true,
            'token' => $token
        ]);
    }

    // Process invitation
    public function processInvite(Request $request, $token)
    {
        if (!Auth::check()) {
            $request->session()->put('invite_token', $token);
            return response()->json([
                'action' => 'login_required'
            ]);
        }

        $workspace = DB::table('workspaces')
            ->where('invite_token', $token)
            ->where('invite_token_expires_at', '>', now())
            ->first();

        if (!$workspace) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired invitation'
            ], 400);
        }

        $userId =  Auth::user()->user_id;

        // Check if already a member
        $isMember = DB::table('workspace_members')
            ->where('workspace_id', $workspace->workspace_id)
            ->where('user_id', $userId)
            ->exists();

        if ($isMember) {
            return response()->json([
                'success' => false,
                'message' => 'You are already a member of this workspace'
            ], 400);
        }

        // Add to workspace
        DB::table('workspace_members')->insert([
            'workspace_id' => $workspace->workspace_id,
            'user_id' => $userId,
            'role' => 'member',
            'joined_at' => now()
        ]);

        // Optionally invalidate token
        DB::table('workspaces')
            ->where('workspace_id', $workspace->workspace_id)
            ->update([
                'invite_token' => null,
                'invite_token_expires_at' => null
            ]);

        return response()->json([
            'success' => true,
            'workspace' => $workspace
        ]);
    }

    // Check pending invites after auth
    public function checkPendingInvites(Request $request)
    {
        if (!$request->session()->has('invite_token')) {
            return response()->json([
                'has_pending_invites' => false
            ]);
        }

        $token = $request->session()->pull('invite_token');
        return $this->processInvite($request, $token);
    }
}
