<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PasswordResetService;
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
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed.'
            ], 422);
        }

        $user = User::where('email', $request->email)->first();


        if (!$user) {
            return response()->json([
                'message' => 'If the email exists, a reset link has been sent.'
            ], 200);
        }

   
        $token = bin2hex(random_bytes(32)); // 64-character secure token

        // $expiry = now()->addMinutes(1);
        $expiry = now()->addMinute()->setSecond(0)->setMicrosecond(0);
        $user->reset_token = $token;
        $user->reset_token_expires_at = $expiry;
        $user->save();
        
        // \Log::info("Generated reset token for user {$user->id}, expires at: {$expiry}");
        
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
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed.'
            ], 422);
        }

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