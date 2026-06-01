<?php

use App\Service\Course\Difficulty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('description', 255)->nullable();
            $table->string('url', '30')->unique();
            $table->enum('difficulty', Difficulty::getAllValues());
            $table->decimal('time_of_passage_hours', 8, 2)->nullable();
            $table->string('intro_img_path', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_course');
    }
};
