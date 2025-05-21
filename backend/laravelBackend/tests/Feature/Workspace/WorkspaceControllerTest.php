<?php

namespace Tests\Feature\Workspace;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // For some assertions if needed
use Illuminate\Support\Str;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class WorkspaceControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user; // General authenticated user for tests

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Storage::fake('public'); // Fake the public disk
    }

    private function createAndActAsUser(string $role = 'owner', ?Workspace $workspace = null): User
    {
        $user = User::factory()->create();
        if ($workspace) {
            WorkspaceMember::factory()->create([
                'workspace_id' => $workspace->workspace_id,
                'user_id' => $user->user_id,
                'role' => $role,
            ]);
        }
        $this->actingAs($user, 'api');
        return $user;
    }

    // --- Test createWorkspace ---

    #[Test]
    public function user_can_create_workspace_successfully()
    {
        $this->actingAs($this->user, 'api');
        $workspaceName = $this->faker->company;
        $workspaceDescription = $this->faker->sentence;

        $response = $this->postJson('/api/workspaces', [
            'name' => $workspaceName,
            'description' => $workspaceDescription,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'workspace' => [
                    'name' => $workspaceName,
                    'description' => $workspaceDescription,
                    'created_by' => $this->user->user_id,
                ]
            ])
            ->assertJsonStructure([
                'success',
                'workspace' => [
                    'workspace_id', 'name', 'description', 'created_by', 'invite_token', 'invite_token_expires_at', 'logo_url', 'created_at', 'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('workspaces', [
            'name' => $workspaceName,
            'created_by' => $this->user->user_id,
        ]);

        $createdWorkspace = Workspace::where('name', $workspaceName)->first();
        $this->assertNotNull($createdWorkspace);

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $createdWorkspace->workspace_id,
            'user_id' => $this->user->user_id,
            'role' => 'owner',
        ]);

        $logoPathPrefix = 'workspace-logos/';
        $allFiles = Storage::disk('public')->files($logoPathPrefix);
        $this->assertNotEmpty($allFiles, "No logo was saved in public/{$logoPathPrefix}");
        $this->assertTrue(Str::endsWith($response->json('workspace.logo_url'), $allFiles[0]));
    }

    #[Test]
    public function create_workspace_fails_without_name()
    {
        $this->actingAs($this->user, 'api');
        $response = $this->postJson('/api/workspaces', [
            'description' => 'A workspace without a name',
        ]);

        $response->assertStatus(422) // Laravel validation error
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function unauthenticated_user_cannot_create_workspace()
    {
        $response = $this->postJson('/api/workspaces', [
            'name' => 'Unauth Workspace',
        ]);
        $response->assertStatus(401);
    }


    #[Test]
    public function user_gets_empty_list_if_no_workspaces()
    {
        $this->actingAs($this->user, 'api');
        $response = $this->getJson('/api/workspaces');
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(0, 'workspaces');
    }


    #[Test]
    public function user_cannot_get_workspace_by_id_if_not_member()
    {
        $workspace = Workspace::factory()->create(); // User is not a member
        $this->actingAs($this->user, 'api');

        $response = $this->getJson("/api/workspaces/{$workspace->workspace_id}");
        $response->assertStatus(403)
                 ->assertJson(['message' => 'Unauthorized - You are not a member of this workspace']);
    }

    #[Test]
    public function get_workspace_by_id_returns_404_for_non_existent_workspace()
    {
        $this->actingAs($this->user, 'api');
        $nonExistentId = (string) Str::uuid();
        $response = $this->getJson("/api/workspaces/{$nonExistentId}");
        $response->assertStatus(404);
    }


    #[Test]
    public function delete_non_existent_workspace_returns_404()
    {
        $owner = $this->createAndActAsUser('owner');
        $nonExistentId = (string) Str::uuid();
        $response = $this->deleteJson("/api/workspaces/{nonExistentId}");
        $response->assertStatus(404);
    }
}
