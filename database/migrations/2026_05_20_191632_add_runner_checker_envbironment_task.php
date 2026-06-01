<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task', function (Blueprint $table): void {
            $table->text('runner_file_path')->nullable();
            $table->text('checker_file_path')->nullable();
            $table->unsignedBigInteger('environment_id')->nullable();
            $table->json('tests');

            $table->foreign('environment_id')
                ->references('id')
                ->on('environment')
                ->onDelete('RESTRICT');
        });
    }

    public function down(): void
    {
        Schema::table('task', function (Blueprint $table): void {
            $table->dropForeign(['environment_id']);
            $table->dropColumn([
                'runner_file_path',
                'checker_file_path',
                'environment_id',
                'tests',
            ]);
        });
    }
};
