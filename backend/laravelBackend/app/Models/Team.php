<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory;

    protected $primaryKey = 'team_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'visibility',
        'created_by',
        'color_code',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->team_id)) {
                $model->team_id = (string) Str::uuid();
            }
        });
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->withPivot('role', 'added_by')
            ->withTimestamps();
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'team_id');
    }
}
