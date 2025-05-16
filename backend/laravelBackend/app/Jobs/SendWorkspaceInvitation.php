<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;

use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Foundation\Bus\Dispatchable;

use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Queue\SerializesModels;

use App\Mail\WorkspaceInvitationMail;

use Illuminate\Support\Carbon;
use Mail;

class SendWorkspaceInvitation implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(

        public string $email,

        public string $workspaceName,

        public string $inviterName,

        public string $inviteLink,

        public string $role,

        public Carbon $expiresAt

    ) {
    }

    public function handle()
    {
        \Log::info("Mail");
        Mail::to($this->email)->send(new WorkspaceInvitationMail(

            $this->workspaceName,

            $this->inviterName,

            $this->inviteLink,

            $this->role,

            $this->expiresAt

        ));

    }

}