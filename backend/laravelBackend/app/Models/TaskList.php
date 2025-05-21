<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskList extends Model
{
    use HasFactory;
    protected $primaryKey = 'list_id';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'lists';

    protected $fillable = [
        'list_id',
        'project_id',
        'name',
        'description',
        'created_by',
        'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'list_id');
    }
    
}
