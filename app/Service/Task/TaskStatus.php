<?php

namespace App\Service\Task;

enum TaskStatus: string
{
    case COMPLETED = 'Completed';
    case INCORRECT_RESULT = 'Incorrect result';
    case MEMORY_LIMIT = 'Memory limit';
    case TIME_LIMIT = 'Time limit';
    case OTHER_ERROR = 'Error';

    static public function getAllValues(): array
    {
        return array_map(fn($value) => $value->value, TaskStatus::cases());
    }
}
