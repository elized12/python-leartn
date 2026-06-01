<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_lesson', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('course_id');

            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')
                ->on('course')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lesson');
    }
};
