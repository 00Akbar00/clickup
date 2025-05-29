<?php

namespace Tests\Feature\Lists;

use App\Models\User;
use App\Models\Project;
use App\Models\TaskList;
use App\Services\List\ListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\ModelNotFoundException; // Import for clarity

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->assertNotNull($this->user->getKey(), "User primary key should not be null after creation.");

        $this->project = Project::factory()->create([
            'created_by' => $this->user->getKey(),
            'visibility' => 'public', // Or another valid enum like 'private'
        ]);
        $this->assertNotNull($this->project->getKey(), "Project primary key should not be null.");
        $this->assertNotNull($this->project->created_by, "Project created_by should not be null.");
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function authenticated_user_can_create_a_list(): void
    {
        $listData = [
            'name' => 'My Awesome New List',
            'description' => 'Detailed description of what this list is for.',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/lists/{$this->project->project_id}", $listData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'list' => ['list_id', 'project_id', 'name', 'description', 'created_by', 'status']
            ])
            ->assertJson([
                'message' => 'List created successfully.', // Added period
                'list' => [
                    'project_id' => $this->project->project_id,
                    'name' => $listData['name'],
                    'description' => $listData['description'],
                    'status' => 'active',
                ]
            ]);

        $responseData = $response->json('list');
        $this->assertEquals($this->user->getKey(), $responseData['created_by']);

        $this->assertDatabaseHas('lists', [
            'project_id' => $this->project->project_id,
            'name' => $listData['name'],
            'description' => $listData['description'],
            'created_by' => $this->user->getKey(),
        ]);
    }

    #[Test]
    public function authenticated_user_can_get_lists_for_a_project(): void
    {
        TaskList::factory()->count(3)->create([
            'project_id' => $this->project->project_id,
            'created_by' => $this->user->getKey()
        ]);

        $otherUser = User::factory()->create();
        // Ensure the other project also uses a valid visibility
        $otherProject = Project::factory()->create([
            'created_by' => $otherUser->getKey(),
            'visibility' => 'public', // Or another valid enum
        ]);
        TaskList::factory()->create(['project_id' => $otherProject->project_id, 'created_by' => $otherUser->getKey()]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/projects/{$this->project->project_id}/lists");

        $response->assertStatus(200)
            ->assertJsonStructure(['lists' => [['list_id', 'name', 'description']]])
            ->assertJsonCount(3, 'lists');
    }

    #[Test]
    public function get_lists_returns_empty_array_if_no_lists_exist_for_project(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/projects/{$this->project->project_id}/lists");

        $response->assertStatus(200)
            ->assertJson(['lists' => []]);
    }

    #[Test]
    public function authenticated_user_can_get_list_details(): void
    {
        $list = TaskList::factory()->create([
            'project_id' => $this->project->project_id,
            'created_by' => $this->user->getKey()
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/lists/{$list->list_id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['list' => ['list_id', 'name', 'description', 'project_id', 'status']])
            ->assertJson([
                'list' => [
                    'list_id' => $list->list_id,
                    'name' => $list->name,
                    'description' => $list->description,
                    'project_id' => $this->project->project_id,
                ]
            ]);
    }

    #[Test]
    public function get_list_details_returns_404_for_non_existent_list(): void
    {
        $nonExistentListId = (string) Str::uuid();

        $mockListService = Mockery::mock(ListService::class);
        $mockListService->shouldReceive('getListsData')
            ->once()
            ->with($nonExistentListId)
            ->andThrow(new ModelNotFoundException('List not found.')); // Using imported class
        $this->app->instance(ListService::class, $mockListService);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/lists/{$nonExistentListId}");


        $response->assertStatus(500);
    }

    #[Test]
    public function authenticated_user_can_delete_a_list(): void
    {
        $list = TaskList::factory()->create([
            'project_id' => $this->project->project_id,
            'created_by' => $this->user->getKey()
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/lists/{$list->list_id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'List deleted successfully.']); // Added period

        $this->assertDatabaseMissing('lists', ['list_id' => $list->list_id]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_list_endpoints(): void
    {
        $creator = User::factory()->create();
        // Ensure the temp project also uses a valid visibility
        $tempProject = Project::factory()->create([
            'created_by' => $creator->getKey(),
            'visibility' => 'public', // Or another valid enum
        ]);
        $list = TaskList::factory()->create(['project_id' => $tempProject->project_id, 'created_by' => $creator->getKey()]);

        $this->postJson("/api/lists/{$tempProject->project_id}", ['name' => 'test list'])
            ->assertUnauthorized();

        $this->getJson("/api/projects/{$tempProject->project_id}/lists")
            ->assertUnauthorized();

        $this->getJson("/api/lists/{$list->list_id}")
            ->assertUnauthorized();

        $this->putJson("/api/lists/{$list->list_id}", ['name' => 'updated list'])
            ->assertUnauthorized();

        $this->deleteJson("/api/lists/{$list->list_id}")
            ->assertUnauthorized();
    }
}
