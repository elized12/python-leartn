<?php

use App\Service\Task\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempt_solution', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->enum('status', TaskStatus::getAllValues());
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('execution_time_s', 8, 3)->nullable();
            $table->unsignedBigInteger('peak_memory_usage_b')->nullable();

            $table->foreign('task_id')
                ->references('id')
                ->on('task')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_solution');
    }
};
