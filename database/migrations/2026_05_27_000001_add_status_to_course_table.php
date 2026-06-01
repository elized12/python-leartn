<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course', function (Blueprint $table) {
            $table->string('status', 20)->default('published')->after('intro_img_path');
        });
    }

    public function down(): void
    {
        Schema::table('course', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
