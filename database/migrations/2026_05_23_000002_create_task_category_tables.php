<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_category', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->unique();
            $table->string('slug')->unique();
        });

        Schema::create('task_category_task', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('task_id')->constrained('task')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('task_category')->cascadeOnDelete();
            $table->unique(['task_id', 'category_id']);
        });

        DB::table('task_category')->insert([
            ['name' => 'массивы', 'slug' => 'massivy'],
            ['name' => 'два указателя', 'slug' => 'dva-ukazatelya'],
            ['name' => 'строки', 'slug' => 'stroki'],
            ['name' => 'динамическое программирование', 'slug' => 'dinamicheskoe-programmirovanie'],
            ['name' => 'графы', 'slug' => 'grafy'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('task_category_task');
        Schema::dropIfExists('task_category');
    }
};
