<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('title');
            $table->decimal('time_limit_s', 8, 3)->nullable();
            $table->unsignedBigInteger('memory_limit_b')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('rating')->default(1200);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task');
    }
};
