<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class CheckResetTokenExpiry
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->reset_token && $user->reset_token_expires_at && $user->reset_token_expires_at < now()) {
            // Reset the expired token
            $user->reset_token = null;
            $user->reset_token_expires_at = null;
            $user->save();
        }

        return $next($request);
    }
}
