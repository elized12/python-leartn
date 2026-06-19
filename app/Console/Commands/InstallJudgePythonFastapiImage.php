<?php

namespace App\Console\Commands;

use App\Jobs\BuildDockerEnvironment;
use App\Models\Task\Environment;
use Illuminate\Console\Command;

class InstallJudgePythonFastapiImage extends Command
{
    protected $signature = 'judge:install-python-fastapi
        {--image=python-learn/judge-python-fastapi:3.12 : Docker image name}
        {--name=Python 3.12 FastAPI Judge : Environment display name}
        {--skip-build : Only create or update the environment record}';

    protected $description = 'Build the Python+FastAPI judge Docker image and register it as an execution environment.';

    public function handle(): int
    {
        $image = (string) $this->option('image');
        $description = 'Окружение Python 3.12 для задач по FastAPI и API-тестированию: fastapi, uvicorn, pydantic, httpx, pytest и /usr/bin/time.';
        $libraries = ['fastapi', 'uvicorn', 'pydantic', 'httpx', 'pytest'];

        if ($this->option('skip-build')) {
            Environment::updateOrCreate(
                ['docker_image_name' => $image],
                [
                    'slug' => 'python-312-fastapi-judge',
                    'name' => (string) $this->option('name'),
                    'description' => $description,
                    'editor_libraries' => $libraries,
                    'is_active' => true,
                ]
            );

            $this->info('Python FastAPI judge environment registered.');
            return self::SUCCESS;
        }

        BuildDockerEnvironment::dispatch(
            (string) $this->option('name'),
            $image,
            $description,
            true,
            base_path('docker/judge-python-fastapi'),
            false,
            $libraries
        );

        $this->info('Python FastAPI judge image build dispatched to queue.');

        return self::SUCCESS;
    }
}
