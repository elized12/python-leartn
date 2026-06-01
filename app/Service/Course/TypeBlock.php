<?php

namespace App\Service\Course;

enum TypeBlock: string
{
    case Text = 'text';
    case ExecutableCode = 'executableCode';
    case Quiz = 'quiz';
    case Divider = 'divider';
    case Video = 'video';
    case InfoBox = 'infoBox';
    case Image = 'image';
    case TaskList = 'taskList';

    static public function getAllValues(): array
    {
        return array_map(fn($value) => $value->value, TypeBlock::cases());
    }
}
