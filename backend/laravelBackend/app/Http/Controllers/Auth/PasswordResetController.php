<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Services\AuthService\PasswordResetService;
use App\Services\VerifyValidationService\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class PasswordResetController extends Controller
{
    protected $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    //Forget Password
    public function forgotPassword(Request $request)
    {
        $validation = ValidationService::emailLoginRules();

        $request->validate(
            $validation['rules'],     
            $validation['messages']   
        );

        $user = User::where('email', $request->email)->first();


        if (!$user) {
            return response()->json([
                'message' => 'If the email exists, a reset link has been sent.'
            ], 200);
        }


        $token = bin2hex(random_bytes(32)); // 64-character secure token

        // $expiry = now()->addMinutes(1);
        $expiry = now()->addHour();
        $user->reset_token = $token;
        $user->reset_token_expires_at = $expiry;
        $user->save();


        // Send reset email via service
        $resetURL = $this->passwordResetService->sendResetLink($user);

        return response()->json([
            'message' => 'If the email exists, a reset link has been sent.',
            '' => $resetURL
        ]);
    }

    //Reset Password
    public function resetPassword(Request $request, $token)
    {
        $validation = ValidationService::passwordRules();
        $request->validate(
            $validation['rules'],     
            $validation['messages']   
        );

        $user = User::where('reset_token', $token)->first();

        if (!$user || !$user->reset_token_expires_at || $user->reset_token_expires_at->isPast()) {
            return response()->json([
                'message' => 'This reset link is invalid or has expired.'
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->reset_token = null;
        $user->reset_token_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Your password has been reset successfully.'
        ]);
    }
    public function showResetPasswordLink($token)
    {
        $user = User::where('reset_token', $token)->first();

        if (!$user || !$user->reset_token_expires_at || $user->reset_token_expires_at->isPast()) {
            return redirect()->back()->with('error', 'This password reset link is invalid or has expired.');
        }

        return view('email.show-reset-password', ['token' => $token]);
    }

}