<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'user_id';

    /**
     * The type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'password',
        'profile_picture_url',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'password',
        'reset_token',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
    ];

    /**
     * Automatically generate a UUID when creating a new user.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->user_id)) {
                $user->user_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the password for authentication.
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * User's associated workspaces.
     */
    public function workspaces()
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members', 'user_id', 'workspace_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * User's associated teams.
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members', 'user_id', 'team_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Tasks assigned to the user.
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_assignees', 'user_id', 'task_id')
            ->withTimestamps();
    }

    /**
     * Workspaces created by the user.
     */
    public function createdWorkspaces()
    {
        return $this->hasMany(Workspace::class, 'created_by');
    }

    /**
     * Teams created by the user.
     */
    public function createdTeams()
    {
        return $this->hasMany(Team::class, 'created_by');
    }

    /**
     * Projects created by the user.
     */
    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * Task lists created by the user.
     */
    public function createdLists()
    {
        return $this->hasMany(TaskList::class, 'created_by');
    }

    /**
     * Tasks created by the user.
     */
    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }
}
