<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('attempt_solution', 'peak_memory_usage_mb')) {
            return;
        }

        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE attempt_solution MODIFY peak_memory_usage_mb DECIMAL(8,1) NULL'),
            'pgsql' => DB::statement('ALTER TABLE attempt_solution ALTER COLUMN peak_memory_usage_mb TYPE DECIMAL(8,1)'),
            default => null,
        };
    }

    public function down(): void
    {
        if (!Schema::hasColumn('attempt_solution', 'peak_memory_usage_mb')) {
            return;
        }

        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE attempt_solution MODIFY peak_memory_usage_mb BIGINT UNSIGNED NULL'),
            'pgsql' => DB::statement('ALTER TABLE attempt_solution ALTER COLUMN peak_memory_usage_mb TYPE BIGINT'),
            default => null,
        };
    }
};
