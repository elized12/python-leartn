<?php

namespace Database\Seeders;

use App\Models\Task\Environment;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Environment::updateOrCreate(
            ['slug' => 'python-3-basic'],
            [
                'name' => 'Python 3 Basic',
                'description' => 'Базовое окружение для Python-задач',
                'docker_image_name' => 'python:3.12-slim',
                'is_active' => true,
            ]
        );
    }
}
