<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\TokenService;
use App\Services\MailService;
use Illuminate\Validation\ValidationException;
use Str;
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\PngEncoder;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    protected $tokenService;
    protected $mailService;
    protected $passwordResetService;

    public function __construct(TokenService $tokenService, MailService $mailService, PasswordResetService $passwordResetService)
    {
        $this->tokenService = $tokenService;
        $this->mailService = $mailService;
        $this->passwordResetService = $passwordResetService;
    }

    // User Signup
    public function signup(Request $request)
    {
        try {
            $request->validate([
                'fullName' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $avatarPath = null;

            if ($request->hasFile('avatar')) {

                $file = $request->file('avatar');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $uuid = Str::uuid()->toString();
                $filename = $originalName . '_' . $uuid . '.' . $extension;

                $avatarPath = $file->storeAs('/avatars', $filename);
            } else {

                $name = $request->input('fullName') ?? 'User';

                $avatarImage = Avatar::create($name)->getImageObject()->encode(new PngEncoder());

                $uuid = Str::uuid()->toString();
                $filename = 'avatars/' . Str::slug($name) . '_' . $uuid . '.png';

                Storage::disk('public')->put($filename, $avatarImage);

                $avatarPath = $filename;
            }


            $user = User::create([
                'id' => (string) Str::uuid(),
                'fullName' => $request->fullName,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'avatar' => $avatarPath,
            ]);

            return response()->json([
                'message' => 'User registered successfully.',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred during signup: ' . $e->getMessage()
            ], 500);
        }
    }

    // User Login
    public function login(Request $request)
    {
        try {
            // Validate the input
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            // Find the user by email
            $user = User::where('email', $validated['email'])->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            // Generate token
            $token = $this->tokenService->generateToken($user);


            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'fullName' => $user->fullName,
                    'email' => $user->email,
                    'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during login',
                'error' => $e->getMessage(),
            ], 500);

        }
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

        $expiry = now()->addMinutes(1);
        
        $user->reset_token = $token;
        $user->reset_token_expires_at = $expiry;
        $user->save();
        
        \Log::info("Generated reset token for user {$user->id}, expires at: {$expiry}");
        
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