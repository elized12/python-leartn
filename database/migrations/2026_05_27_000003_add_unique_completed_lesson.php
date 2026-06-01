<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('completed_lesson', function (Blueprint $table) {
            $table->unique(['user_id', 'course_lesson_id'], 'completed_lesson_user_lesson_unique');
        });
    }

    public function down(): void
    {
        Schema::table('completed_lesson', function (Blueprint $table) {
            $table->dropUnique('completed_lesson_user_lesson_unique');
        });
    }
};
