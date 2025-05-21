<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkspaceMember extends Model
{
    use HasFactory;

    protected $primaryKey = 'workspace_member_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'workspace_member_id',
        'workspace_id',
        'user_id',
        'role',
        'joined_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function taskAssignees()
    {
        return $this->hasMany(TaskAssignee::class, 'workspace_member_id');
    }
}
