<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class ClearExpiredResetTokens extends Command
{
    protected $signature = 'reset-tokens:clear';
    protected $description = 'Clear expired password reset tokens';

    public function handle()
    {
        // Remove microseconds to match database precision


        

        $now = now()->setSecond(0)->setMicrosecond(0);

        // \Log::info('Now: ' . $now->toDateTimeString());

        $expiredUsers = User::whereNotNull('reset_token')
            ->where('reset_token_expires_at', '<=', $now)
            ->get();
        
        // \Log::info('Found ' . $expiredUsers->count() . ' expired users');
    







        \Log::info("Found " . $expiredUsers->count() . " expired users");

        foreach ($expiredUsers as $user) {
            \Log::info("Clearing token for user ID: " . $user->id);
            $user->reset_token = null;
            $user->reset_token_expires_at = null;
            $user->save();
        }

        $this->info("Expired reset tokens cleared: {$expiredUsers->count()} users affected.");
    }


}