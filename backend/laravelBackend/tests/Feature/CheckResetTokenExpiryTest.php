<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User; // Ensure this is your correct User model
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use App\Http\Middleware\CheckResetTokenExpiry;
use Carbon\Carbon;

class CheckResetTokenExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', CheckResetTokenExpiry::class])->group(function () {
            Route::get('/test-middleware-route', function () {
                return response('OK', 200);
            });
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon after each test
        parent::tearDown();
    }

    public function test_clears_reset_token_if_it_is_expired_for_authenticated_user()
    {
        Carbon::setTestNow(now());

        $user = User::factory()->create([
            'reset_token' => 'some_expired_token',
            'reset_token_expires_at' => now()->subHour(),
        ]);

        // This assertion should now pass if getKey() returns the PK value
        $this->assertNotNull($user->getKey(), "User Primary Key (getKey()) is null immediately after creation.");
        // We remove the $user->id check as 'id' is likely not the attribute holding the PK value directly

        $this->actingAs($user);

        $response = $this->get('/test-middleware-route');
        $response->assertStatus(200);

        $user->refresh();

        $this->assertDatabaseHas('users', [
            $user->getKeyName() => $user->getKey(), // Use actual PK name and its value
            'reset_token' => null,
            'reset_token_expires_at' => null,
        ]);

        Carbon::setTestNow();
    }

    public function test_does_not_clear_reset_token_if_it_is_not_expired_for_authenticated_user()
    {
        Carbon::setTestNow(now());
        $token = 'some_valid_token';
        $expiresAt = now()->addHour();

        $user = User::factory()->create([
            'reset_token' => $token,
            'reset_token_expires_at' => $expiresAt,
        ]);
        $this->assertNotNull($user->getKey(), "User Primary Key (getKey()) is null immediately after creation.");

        $this->actingAs($user);
        $response = $this->get('/test-middleware-route');
        $response->assertStatus(200);

        $user->refresh();

        $this->assertDatabaseHas('users', [
            $user->getKeyName() => $user->getKey(),
            'reset_token' => $token,
            'reset_token_expires_at' => $expiresAt->toDateTimeString(),
        ]);
        Carbon::setTestNow();
    }

    public function test_does_not_modify_user_if_no_reset_token_exists_for_authenticated_user()
    {
        Carbon::setTestNow(now());
        $user = User::factory()->create([
            'reset_token' => null,
            'reset_token_expires_at' => null,
        ]);
        $this->assertNotNull($user->getKey(), "User Primary Key (getKey()) is null immediately after creation.");

        $this->actingAs($user);
        $response = $this->get('/test-middleware-route');
        $response->assertStatus(200);

        $user->refresh();

        $this->assertDatabaseHas('users', [
            $user->getKeyName() => $user->getKey(),
            'reset_token' => null,
            'reset_token_expires_at' => null,
        ]);
        Carbon::setTestNow();
    }

    public function test_does_not_modify_user_if_only_reset_token_exists_without_expiry_for_authenticated_user()
    {
        Carbon::setTestNow(now());
        $user = User::factory()->create([
            'reset_token' => 'some_token_no_expiry',
            'reset_token_expires_at' => null,
        ]);
        $this->assertNotNull($user->getKey(), "User Primary Key (getKey()) is null immediately after creation.");

        $this->actingAs($user);
        $response = $this->get('/test-middleware-route');
        $response->assertStatus(200);

        $user->refresh();

        $this->assertDatabaseHas('users', [
            $user->getKeyName() => $user->getKey(),
            'reset_token' => 'some_token_no_expiry',
            'reset_token_expires_at' => null,
        ]);
        Carbon::setTestNow();
    }

    public function test_allows_request_to_proceed_for_guest_user()
    {
        $response = $this->get('/test-middleware-route');
        $response->assertStatus(200);
    }
}
