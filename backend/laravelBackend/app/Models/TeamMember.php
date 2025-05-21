<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;

    protected $primaryKey = 'team_member_id';

    public $incrementing = false;
    protected $keyType = 'string';


    protected $fillable = [
        'team_member_id',
        'team_id',
        'user_id',
        'role',
        'added_by',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
