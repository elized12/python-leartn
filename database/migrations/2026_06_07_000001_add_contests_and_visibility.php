<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task', function (Blueprint $table) {
            $table->boolean('is_public')->default(true)->after('example');
        });

        Schema::create('contest', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('contest_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained('contest')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('task')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['contest_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contest_task');
        Schema::dropIfExists('contest');

        Schema::table('task', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
};
