<?php

namespace App\Models;

use App\Service\Message\MessageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notification';

    protected $fillable = [
        'id',
        'message',
        'type',
        'receiver_id',
        'visible'
    ];

    protected $casts = [
        'type' => MessageType::class
    ];
}
