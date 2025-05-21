<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskAssignee extends Model
{
    use HasFactory;

    protected $primaryKey = 'task_assignee_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'task_assignee_id',
        'task_id',
        'team_member_id',
        'assigned_by',
        'assigned_at',
    ];

    public function workspaceMember()
    {
        return $this->belongsTo(WorkspaceMember::class, 'workspace_member_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
    public function teamMember()
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }
}
