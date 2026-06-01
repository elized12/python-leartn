<?php

use App\Service\Course\TypeBlock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_block', function (Blueprint $table) {
            $table->id();
            $table->enum('type', TypeBlock::getAllValues());
            $table->integer('order')->default(0);
            $table->json('params');
            $table->unsignedBigInteger('course_lesson_id');
            $table->timestamps();

            $table->foreign('course_lesson_id')
                ->references('id')
                ->on('course_lesson')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_block');
    }
};
