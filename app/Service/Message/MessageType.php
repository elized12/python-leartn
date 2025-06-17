<?php

namespace App\Service\Message;

enum MessageType: string
{
    case ERROR = 'Error';
    case SUCCESS = 'Success';

    static public function getAllValues(): array
    {
        return array_map(fn($value) => $value->value, MessageType::cases());
    }
}
