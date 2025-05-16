@component('mail::message')
# Workspace Invitation

Hi!

You've been invited by **{{ $inviterName }}** to join the workspace **{{ $workspaceName }}** as a **{{ $role }}**.

@component('mail::button', ['url' => $inviteLink])
Join Workspace
@endcomponent

The invitation link expires on {{ $expiresAt->format('F j, Y') }}.

If you didn't expect to receive this invitation, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent