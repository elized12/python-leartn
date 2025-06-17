<?php

namespace App\Models;

use App\Service\Message\MessageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifacation extends Model
{
    use HasFactory;

    protected $table = 'notifaction';

    protected $fillable = [
        'id',
        'message',
        'type',
        'receiver_id',
    ];

    protected $casts = [
        'type' => MessageType::class
    ];
}
