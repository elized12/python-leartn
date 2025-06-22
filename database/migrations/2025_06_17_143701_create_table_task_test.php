<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_test', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->text('input')->nullable();
            $table->text('expected_output');

            $table->unsignedBigInteger('number')->default(1);

            $table->foreign('task_id')
                ->references('id')
                ->on('task')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_test');
    }
};
