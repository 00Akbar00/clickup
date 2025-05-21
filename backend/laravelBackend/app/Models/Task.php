<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $primaryKey = 'task_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'task_id',
        'list_id',
        'title',
        'description',
        'created_by',
        'due_date',
        'priority',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function list()
    {
        return $this->belongsTo(TaskList::class, 'list_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignees()
    {
        return $this->belongsToMany(WorkspaceMember::class, 'task_assignees', 'task_id', 'workspace_member_id');
    }
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
    public function listTask()
    {
        return $this->belongsTo(TaskList::class, 'list_id'); 
    }

}
