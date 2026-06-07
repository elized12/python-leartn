<?php

namespace App\Models\Task;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ContestParticipant extends Model
{
    protected $table = 'contest_participant';

    protected $fillable = [
        'contest_id',
        'user_id',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function contest()
    {
        return $this->belongsTo(Contest::class, 'contest_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
