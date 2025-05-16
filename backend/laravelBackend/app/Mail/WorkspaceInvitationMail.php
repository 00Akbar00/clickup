<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class WorkspaceInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $workspaceName;
    public $inviterName;
    public $inviteLink;
    public $role;
    public $expiresAt;

    /**
     * Create a new message instance.
     *
     * @param string $workspaceName
     * @param string $inviterName
     * @param string $inviteLink
     * @param string $role
     * @param Carbon $expiresAt
     */
    public function __construct($workspaceName, $inviterName, $inviteLink, $role, $expiresAt)
    {
        $this->workspaceName = $workspaceName;
        $this->inviterName = $inviterName;
        $this->inviteLink = $inviteLink;
        $this->role = $role;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Invitation to join {$this->workspaceName}")
                   ->markdown('emails.workspace_invitation');
    }
}