<?php

return [
    'storage_volume' => env('JUDGE_STORAGE_VOLUME'),
    'docker_uid' => env('JUDGE_DOCKER_UID', 1000),
    'docker_gid' => env('JUDGE_DOCKER_GID', 1000),
    'wall_timeout_multiplier' => (int) env('JUDGE_WALL_TIMEOUT_MULTIPLIER', 10),
    'wall_timeout_grace_s' => (int) env('JUDGE_WALL_TIMEOUT_GRACE_S', 10),
    'min_wall_timeout_s' => (int) env('JUDGE_MIN_WALL_TIMEOUT_S', 30),
    'output_limit_bytes' => (int) env('JUDGE_OUTPUT_LIMIT_BYTES', 1048576),
    'memory_overhead_mb' => (int) env('JUDGE_MEMORY_OVERHEAD_MB', 32),
    'cpu_shares' => (int) env('JUDGE_CPU_SHARES', 2048),
    'nice' => env('JUDGE_NICE'),
];
