<?php

namespace Tests\Feature;

use App\Services\AuthService\TokenService;
use Carbon\Carbon;
use Tests\TestCase;
use stdClass; // For creating a mock user object

class TokenServiceTest extends TestCase
{
    private TokenService $tokenService;
    private string $testJwtSecret;
    private stdClass $mockUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testJwtSecret = env('JWT_SECRET');
        if (empty($this->testJwtSecret)) {
            $this->fail('JWT_SECRET environment variable is not set for testing. Please set it in phpunit.xml.');
        }
        $this->tokenService = new TokenService(); // Relies on env('JWT_SECRET')

        $this->mockUser = new stdClass();
        $this->mockUser->user_id = 'test-user-123'; // Can be int or string based on your system
        $this->mockUser->full_name = 'Test User FullName';
        $this->mockUser->email = 'testuser@example.com';
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon after each test
        parent::tearDown();
    }

    private function base64UrlDecode(string $data): array
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'));
        return json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
    }

    /** @test */
    public function it_can_generate_a_valid_jwt_token()
    {
        Carbon::setTestNow(Carbon::create(2025, 5, 19, 12, 0, 0)); // Fix current time for predictable iat/exp

        $token = $this->tokenService->generateToken($this->mockUser);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        $parts = explode('.', $token);
        $this->assertCount(3, $parts, "Token does not have 3 parts.");

        // Test Header
        $header = $this->base64UrlDecode($parts[0]);
        $this->assertEquals('HS256', $header['alg']);
        $this->assertEquals('JWT', $header['typ']);

        // Test Payload
        $payload = $this->base64UrlDecode($parts[1]);
        $this->assertEquals(Carbon::now()->timestamp, $payload['iat']);
        $this->assertEquals(Carbon::now()->addHours(9)->timestamp, $payload['exp']);
        $this->assertEquals($this->mockUser->user_id, $payload['sub']);
        $this->assertEquals($this->mockUser->full_name, $payload['name']);
        $this->assertEquals($this->mockUser->email, $payload['email']);

        // Test if the generated token can be validated by the same service
        $validatedPayload = $this->tokenService->validateToken($token);
        $this->assertNotNull($validatedPayload, "Generated token could not be validated.");
        $this->assertEquals($this->mockUser->user_id, $validatedPayload['sub']);
    }

    /** @test */
    public function validate_token_returns_payload_for_a_valid_token()
    {
        Carbon::setTestNow(Carbon::create(2025, 5, 19, 12, 0, 0));
        $token = $this->tokenService->generateToken($this->mockUser);

        // Advance time slightly, but still within validity period
        Carbon::setTestNow(Carbon::now()->addHours(1));

        $payload = $this->tokenService->validateToken($token);

        $this->assertNotNull($payload);
        $this->assertEquals($this->mockUser->user_id, $payload['sub']);
        $this->assertEquals($this->mockUser->full_name, $payload['name']);
        $this->assertEquals($this->mockUser->email, $payload['email']);
        $this->assertEquals(Carbon::create(2025, 5, 19, 12, 0, 0)->timestamp, $payload['iat']); // Original iat
    }

    /** @test */
    public function validate_token_returns_null_for_invalid_token_structure()
    {
        $this->assertNull($this->tokenService->validateToken("invalidtoken"));
        $this->assertNull($this->tokenService->validateToken("part1.part2"));
        $this->assertNull($this->tokenService->validateToken("part1.part2.part3.part4"));
    }

    /** @test */
    public function validate_token_returns_null_for_expired_token()
    {
        Carbon::setTestNow(Carbon::create(2025, 5, 19, 12, 0, 0)); // Token generation time
        $token = $this->tokenService->generateToken($this->mockUser);

        // Advance time past expiration (default is 9 hours)
        Carbon::setTestNow(Carbon::now()->addHours(9)->addSecond()); // Expired by 1 second

        $this->assertNull($this->tokenService->validateToken($token));
    }


    /** @test */
    public function decode_token_returns_payload_without_validation()
    {
        Carbon::setTestNow(Carbon::create(2025, 5, 19, 12, 0, 0));
        $token = $this->tokenService->generateToken($this->mockUser);

        // 1. Valid token
        $payload = $this->tokenService->decodeToken($token);
        $this->assertNotNull($payload);
        $this->assertEquals($this->mockUser->user_id, $payload['sub']);
        $this->assertEquals(Carbon::now()->timestamp, $payload['iat']);

        // 2. Tampered signature token (should still decode payload)
        $parts = explode('.', $token);
        $tamperedToken = $parts[0] . '.' . $parts[1] . '.' . 'tamperedSignaturePart123';
        $payloadFromTampered = $this->tokenService->decodeToken($tamperedToken);
        $this->assertNotNull($payloadFromTampered);
        $this->assertEquals($this->mockUser->user_id, $payloadFromTampered['sub']); // Payload is intact

        // 3. Expired token (should still decode payload)
        Carbon::setTestNow(Carbon::now()->addHours(10)); // Make token expired
        $payloadFromExpired = $this->tokenService->decodeToken($token);
        $this->assertNotNull($payloadFromExpired);
        $this->assertEquals($this->mockUser->user_id, $payloadFromExpired['sub']);
    }

    /** @test */
    public function decode_token_returns_null_for_invalid_token_structure()
    {
        $this->assertNull($this->tokenService->decodeToken("invalidtoken"));
        $this->assertNull($this->tokenService->decodeToken("part1.part2"));
    }

}
