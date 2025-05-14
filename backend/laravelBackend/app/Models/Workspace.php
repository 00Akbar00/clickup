<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    use HasFactory;

    protected $primaryKey = 'workspace_id';

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'invite_token',
        'invite_token_expires_at',
        'logo_url',
    ];

    protected $casts = [
        'invite_token_expires_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'workspace_members', 'workspace_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function teams()
    {
        return $this->hasMany(Team::class, 'workspace_id');
    }
}