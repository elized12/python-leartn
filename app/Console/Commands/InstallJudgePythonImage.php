<?php

namespace App\Console\Commands;

use App\Jobs\BuildDockerEnvironment;
use App\Models\Task\Environment;
use Illuminate\Console\Command;

class InstallJudgePythonImage extends Command
{
    protected $signature = 'judge:install-python
        {--image=python-learn/judge-python:3.12 : Docker image name}
        {--name=Python 3.12 Judge : Environment display name}
        {--skip-build : Only create or update the environment record}';

    protected $description = 'Build the standard Python judge Docker image and register it as an execution environment.';

    public function handle(): int
    {
        $image = (string) $this->option('image');

        if ($this->option('skip-build')) {
            Environment::updateOrCreate(
                ['docker_image_name' => $image],
                [
                    'slug' => 'python-312-judge',
                    'name' => (string) $this->option('name'),
                    'description' => 'Стандартное окружение Python 3.12 с /usr/bin/time для измерения памяти.',
                    'editor_libraries' => [],
                    'is_active' => true,
                ]
            );

            $this->info('Python judge environment registered.');
            return self::SUCCESS;
        }

        BuildDockerEnvironment::dispatch(
            (string) $this->option('name'),
            $image,
            'Стандартное окружение Python 3.12 с /usr/bin/time для измерения памяти.',
            true,
            base_path('docker/judge-python'),
            false,
            []
        );

        $this->info('Python judge image build dispatched to queue.');

        return self::SUCCESS;
    }
}
