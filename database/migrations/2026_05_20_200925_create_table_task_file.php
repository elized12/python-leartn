<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_file', function (Blueprint $table): void {
            $table->id();
            $table->string('file_path');
            $table->unsignedBigInteger('task_id');
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->foreign('task_id')
                ->references('id')
                ->on('task')
                ->onDelete('CASCADE');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_file');
    }
};
