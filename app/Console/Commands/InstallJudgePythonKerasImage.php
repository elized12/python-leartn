<?php

namespace App\Console\Commands;

use App\Jobs\BuildDockerEnvironment;
use App\Models\Task\Environment;
use Illuminate\Console\Command;

class InstallJudgePythonKerasImage extends Command
{
    protected $signature = 'judge:install-python-keras
        {--image=python-learn/judge-python-keras:3.12 : Docker image name}
        {--name=Python 3.12 Keras Judge : Environment display name}
        {--skip-build : Only create or update the environment record}';

    protected $description = 'Build the Python+Keras judge Docker image and register it as an execution environment.';

    public function handle(): int
    {
        $image = (string) $this->option('image');
        $description = 'Окружение Python 3.12 для изучения Keras и ML: keras, tensorflow, numpy, pandas, scikit-learn, matplotlib и /usr/bin/time.';
        $libraries = ['keras', 'tensorflow', 'numpy', 'pandas', 'scikit-learn', 'matplotlib'];

        if ($this->option('skip-build')) {
            Environment::updateOrCreate(
                ['docker_image_name' => $image],
                [
                    'slug' => 'python-312-keras-judge',
                    'name' => (string) $this->option('name'),
                    'description' => $description,
                    'editor_libraries' => $libraries,
                    'is_active' => true,
                ]
            );

            $this->info('Python Keras judge environment registered.');
            return self::SUCCESS;
        }

        BuildDockerEnvironment::dispatch(
            (string) $this->option('name'),
            $image,
            $description,
            true,
            base_path('docker/judge-python-keras'),
            false,
            $libraries
        );

        $this->info('Python Keras judge image build dispatched to queue.');

        return self::SUCCESS;
    }
}
