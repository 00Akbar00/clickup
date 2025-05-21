<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test; // Import the Test attribute

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    // No specific setUp needed for these tests beyond what TestCase and traits provide

    #[Test]
    public function login_requires_an_email(): void
    {
        // 2. Act
        $response = $this->postJson('api/login', [
            'password' => 'Password123!', // Use a password that would pass format validation
        ]);

        // 3. Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function login_requires_a_password(): void
    {
        // 2. Act
        $response = $this->postJson('api/login', [
            'email' => 'testuser@example.com',
        ]);

        // 3. Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function login_requires_a_valid_email_format(): void
    {
        // 2. Act
        $response = $this->postJson('api/login', [
            'email' => 'not-an-email',
            'password' => 'Password123!', // Use a password that would pass format validation
        ]);

        // 3. Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function login_password_must_be_a_string(): void
    {
        // 1. Arrange
        User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        // 2. Act
        $response = $this->postJson('api/login', [
            'email' => 'testuser@example.com',
            'password' => 12345, // Sending password as an integer
        ]);

        // 3. Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function throttle_limits_login_attempts(): void
    {
        User::factory()->create([
            'email' => 'throttle@example.com',
            'password' => Hash::make('Password123!'), // Store a complex password
        ]);

        $maxAttempts = 5; // Adjust if your app's throttle limit is different

        for ($i = 0; $i <= $maxAttempts; $i++) {
            $response = $this->postJson('api/login', [
                'email' => 'throttle@example.com',
                'password' => 'WrongPass123!', // Keep failing with a password that passes format validation
            ]);
        }


        $response->assertStatus(429); // Too Many Requests
    }
}
