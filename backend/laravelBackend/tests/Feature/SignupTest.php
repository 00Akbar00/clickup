<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthService\AvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test; // Import for PHPUnit attributes

class SignupTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $complexPassword = 'Password123!';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockAvatarService(string $expectedPath = 'avatars/generated_avatar.png'): void
    {
        $this->instance(AvatarService::class, Mockery::mock(AvatarService::class, function (MockInterface $mock) use ($expectedPath) {
            $mock->shouldReceive('generate')
                ->once()
                ->andReturn($expectedPath);
        }));
    }

    private function mockAvatarServiceHandlesNoFile(): void
    {
        $this->instance(AvatarService::class, Mockery::mock(AvatarService::class, function (MockInterface $mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->andReturnNull();
        }));
    }

    #[Test]
    public function user_can_register_successfully_with_profile_picture(): void
    {
        Storage::fake('public');
        $fakeAvatarPath = 'avatars/' . Str::random(10) . '.png';
        $this->mockAvatarService($fakeAvatarPath);

        $testEmail = 'signup.user.' . Str::random(5) . '@gmail.com'; // For DNS check if applicable

        $userData = [
            'full_name' => $this->faker->firstName() . ' ' . $this->faker->lastName(), // Safer full_name
            'email' => $testEmail,
            'password' => $this->complexPassword,
            'password_confirmation' => $this->complexPassword,
            'profile_picture_url' => UploadedFile::fake()->image('avatar.jpg', 100, 100)->size(1024),
        ];

        $response = $this->postJson(route('signup'), $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['user_id', 'full_name', 'email', 'profile_picture_url', 'created_at', 'updated_at'],
            ])
            ->assertJson([
                'message' => 'User registered',
                'user' => [
                    'full_name' => $userData['full_name'],
                    'email' => $userData['email'],
                    'profile_picture_url' => asset('storage/' . $fakeAvatarPath),
                ],
            ]);


        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'full_name' => $userData['full_name'],
            'profile_picture_url' => asset('storage/' . $fakeAvatarPath),
        ]);

        $user = User::where('email', $userData['email'])->first();
        $this->assertNotNull($user, "User was not created in the database.");
        $this->assertTrue(Hash::check($this->complexPassword, $user->password));
        $this->assertNotNull($user->user_id);
    }

    #[Test]
    public function user_can_register_successfully_without_profile_picture(): void
    {
        $this->mockAvatarServiceHandlesNoFile();
        $testEmail = 'signup.noavatar.user.' . Str::random(5) . '@gmail.com';

        $userData = [
            // Using a safer way to generate full_name to avoid validation issues with periods etc.
            'full_name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $testEmail,
            'password' => $this->complexPassword,
            'password_confirmation' => $this->complexPassword,
        ];

        $response = $this->postJson(route('signup'), $userData);


        $expectedProfilePicUrlInResponse = url('storage');
        $expectedProfilePicUrlInDb = url('storage'); // Storing full base URL in DB is unusual

        $response->assertStatus(201)
            ->assertJsonPath('message', 'User registered')
            ->assertJsonPath('user.full_name', $userData['full_name'])
            ->assertJsonPath('user.email', $userData['email'])
            ->assertJsonPath('user.profile_picture_url', $expectedProfilePicUrlInResponse);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'full_name' => $userData['full_name'],
            'profile_picture_url' => $expectedProfilePicUrlInDb,
        ]);

        $user = User::where('email', $userData['email'])->first();
        $this->assertNotNull($user, "User was not created in the database.");
        $this->assertTrue(Hash::check($this->complexPassword, $user->password));
        $this->assertNotNull($user->user_id);
    }

    #[Test]
    public function test_registration_fails_with_missing_full_name(): void
    {
        $userData = [
            'email' => $this->faker->unique()->safeEmail, // Uses @example.org etc.
            'password' => $this->complexPassword,
            'password_confirmation' => $this->complexPassword,
        ];
        $response = $this->postJson(route('signup'), $userData);
        $response->assertStatus(422)->assertJsonValidationErrors(['full_name']);
    }

    #[Test]
    public function test_registration_fails_with_missing_email(): void
    {
        $userData = [
            'full_name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'password' => $this->complexPassword,
            'password_confirmation' => $this->complexPassword,
        ];
        $response = $this->postJson(route('signup'), $userData);
        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function test_registration_fails_with_invalid_email(): void
    {
        $userData = [
            'full_name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => 'not-an-email',
            'password' => $this->complexPassword,
            'password_confirmation' => $this->complexPassword,
        ];
        $response = $this->postJson(route('signup'), $userData);
        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function test_registration_fails_with_missing_password(): void
    {
        $userData = [
            'full_name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail,
        ];
        $response = $this->postJson(route('signup'), $userData);
        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function test_registration_fails_with_short_password(): void
    {
        $userData = [
            'full_name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail,
            'password' => '123',
            'password_confirmation' => '123',
        ];
        $response = $this->postJson(route('signup'), $userData);
        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }


    #[Test]
    public function test_registration_fails_with_invalid_profile_picture_type(): void
    {
        Storage::fake('public');
        $userData = [
            'full_name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail,
            'password' => $this->complexPassword,
            'password_confirmation' => $this->complexPassword,
            'profile_picture_url' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ];
        $response = $this->postJson(route('signup'), $userData);
        $response->assertStatus(422)->assertJsonValidationErrors(['profile_picture_url']);
    }

    #[Test]
    public function test_registration_fails_with_profile_picture_too_large(): void
    {
        Storage::fake('public');
        $userData = [
            'full_name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail,
            'password' => $this->complexPassword,
            'password_confirmation' => $this->complexPassword,
            'profile_picture_url' => UploadedFile::fake()->image('avatar.jpg')->size(3000),
        ];
        $response = $this->postJson(route('signup'), $userData);
        $response->assertStatus(422)->assertJsonValidationErrors(['profile_picture_url']);
    }
}
