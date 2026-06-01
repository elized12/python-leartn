<?php

namespace App\Service\Course;

enum Difficulty: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    static public function getAllValues(): array
    {
        return array_map(fn($value) => $value->value, Difficulty::cases());
    }
}
