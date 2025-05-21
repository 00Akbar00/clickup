<?php

namespace Tests\Feature\Workspace;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceInvitation;
use App\Jobs\SendWorkspaceInvitation; // For testing job dispatch
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue; // For testing jobs
use Illuminate\Support\Facades\Config; // For frontend_url
use Illuminate\Support\Str;
use Carbon\Carbon;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class WorkspaceInviteControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $owner;
    private User $admin;
    private User $member;
    private User $nonMember;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure a test frontend URL
        Config::set('app.frontend_url', 'http://localhost:5173/');

        $this->workspace = Workspace::factory()->create();

        $this->owner = User::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $this->workspace->workspace_id,
            'user_id' => $this->owner->user_id,
            'role' => 'owner',
        ]);

        $this->admin = User::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $this->workspace->workspace_id,
            'user_id' => $this->admin->user_id,
            'role' => 'admin',
        ]);

        $this->member = User::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $this->workspace->workspace_id,
            'user_id' => $this->member->user_id,
            'role' => 'member',
        ]);

        $this->nonMember = User::factory()->create(); // User not part of this workspace
    }

    // --- generateInviteLink Tests ---

    #[Test]
    public function admin_can_generate_invite_link()
    {
        $this->actingAs($this->admin, 'api');

        $response = $this->postJson("/api/workspaces/{$this->workspace->workspace_id}/invites/link");

        $response->assertStatus(200);
    }

    // --- sendInvitations Tests ---

    #[Test]
    public function send_invitations_requires_valid_emails_and_role()
    {
        $this->actingAs($this->owner, 'api');

        $this->workspace->update([
            'invite_token' => Str::random(32),
            'invite_token_expires_at' => now()->addHours(48)
        ]);

        $response = $this->postJson("/api/workspaces/{$this->workspace->workspace_id}/invites/send", [
            'emails' => ['not-an-email'],
            'role' => 'invalid-role',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['emails.0', 'role']);
    }

    #[Test]
    public function send_invitations_fails_if_workspace_token_is_missing_or_expired()
    {
        $this->actingAs($this->owner, 'api');

        $this->workspace->update(['invite_token' => null]); // No token

        $response = $this->postJson("/api/workspaces/{$this->workspace->workspace_id}/invites/send", [
            'emails' => [$this->faker->email],
            'role' => 'member',
        ]);

        $response->assertStatus(400)
                 ->assertJsonPath('message', 'Invite token missing or expired. Please generate invite link first.');
    }
}
