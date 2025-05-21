<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthService\MailService;
use App\Mail\WelcomeMail; // Ensure this namespace is correct
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Illuminate\Mail\Message; // For checking Mail::raw

class MailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MailService $mailService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailService = new MailService();
        Mail::fake(); // Fake mail sending for all tests in this class
    }

    /**
     * @test
     */
    public function send_welcome_email_is_not_sent_if_method_not_called(): void
    {
        User::factory()->create(); // Create a user, but don't send email

        Mail::assertNotSent(WelcomeMail::class);
    }

    /**
     * @test
     */
    public function send_otp_email_is_not_sent_if_method_not_called(): void
    {
        User::factory()->create(); // Create a user

        Mail::assertNotSent(Message::class, function (Message $message) {
            return $message->getSubject() === 'Your Password Reset OTP';
        });

        Mail::assertNothingSent();
    }
}
