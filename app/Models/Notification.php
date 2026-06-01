<?php

namespace App\Models;

use App\Service\Notification\NotificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notification';

    protected $fillable = [
        'id',
        'content',
        'type',
        'receiver_id',
        'visible',
        'type'
    ];

    protected $casts = [
        'type' => NotificationType::class
    ];
}
