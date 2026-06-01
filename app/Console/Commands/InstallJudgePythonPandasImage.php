<?php

namespace App\Console\Commands;

use App\Jobs\BuildDockerEnvironment;
use App\Models\Task\Environment;
use Illuminate\Console\Command;

class InstallJudgePythonPandasImage extends Command
{
    protected $signature = 'judge:install-python-pandas
        {--image=python-learn/judge-python-pandas:3.12 : Docker image name}
        {--name=Python 3.12 Pandas Judge : Environment display name}
        {--skip-build : Only create or update the environment record}';

    protected $description = 'Build the Python+pandas judge Docker image and register it as an execution environment.';

    public function handle(): int
    {
        $image = (string) $this->option('image');

        if ($this->option('skip-build')) {
            Environment::updateOrCreate(
                ['docker_image_name' => $image],
                [
                    'slug' => 'python-312-pandas-judge',
                    'name' => (string) $this->option('name'),
                    'description' => 'Окружение Python 3.12 для анализа данных: pandas, numpy, openpyxl, pyarrow и /usr/bin/time.',
                    'editor_libraries' => ['pandas', 'numpy', 'openpyxl', 'pyarrow'],
                    'is_active' => true,
                ]
            );

            $this->info('Python pandas judge environment registered.');
            return self::SUCCESS;
        }

        BuildDockerEnvironment::dispatch(
            (string) $this->option('name'),
            $image,
            'Окружение Python 3.12 для анализа данных: pandas, numpy, openpyxl, pyarrow и /usr/bin/time.',
            true,
            base_path('docker/judge-python-pandas'),
            false,
            ['pandas', 'numpy', 'openpyxl', 'pyarrow']
        );

        $this->info('Python pandas judge image build dispatched to queue.');

        return self::SUCCESS;
    }
}
