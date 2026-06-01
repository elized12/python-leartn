<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environment', function (Blueprint $table) {
            $table->json('editor_libraries')->nullable()->after('docker_image_name');
        });

        Schema::table('task', function (Blueprint $table) {
            $table->longText('starter_code')->nullable()->after('reference_solution');
        });
    }

    public function down(): void
    {
        Schema::table('task', function (Blueprint $table) {
            $table->dropColumn('starter_code');
        });

        Schema::table('environment', function (Blueprint $table) {
            $table->dropColumn('editor_libraries');
        });
    }
};
