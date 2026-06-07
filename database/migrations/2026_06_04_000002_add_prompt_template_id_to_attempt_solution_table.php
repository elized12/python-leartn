<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempt_solution', function (Blueprint $table) {
            $table->unsignedBigInteger('prompt_template_id')->nullable()->after('user_id');

            $table->foreign('prompt_template_id')
                ->references('id')
                ->on('ai_prompt_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attempt_solution', function (Blueprint $table) {
            $table->dropForeign(['prompt_template_id']);
            $table->dropColumn('prompt_template_id');
        });
    }
};
