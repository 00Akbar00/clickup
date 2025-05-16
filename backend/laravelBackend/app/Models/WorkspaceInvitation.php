<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceInvitation extends Model
{
    protected $primaryKey = 'invitation_id';
    public $incrementing = false;
    protected $keyType = 'string';    protected $fillable = [
        'invitation_id',
        'workspace_id',
        'email',
        'role',
        'inviter_name',
        'invite_token',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
