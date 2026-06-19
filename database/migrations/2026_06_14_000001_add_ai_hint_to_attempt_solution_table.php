<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('attempt_solution', 'ai_hint')) {
            Schema::table('attempt_solution', function (Blueprint $table) {
                $table->longText('ai_hint')->nullable()->after('prompt_template_id');
            });
        }

        if (!Schema::hasColumn('attempt_solution', 'ai_hint_generated_at')) {
            Schema::table('attempt_solution', function (Blueprint $table) {
                $table->timestamp('ai_hint_generated_at')->nullable()->after('ai_hint');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attempt_solution', 'ai_hint_generated_at')) {
            Schema::table('attempt_solution', function (Blueprint $table) {
                $table->dropColumn('ai_hint_generated_at');
            });
        }

        if (Schema::hasColumn('attempt_solution', 'ai_hint')) {
            Schema::table('attempt_solution', function (Blueprint $table) {
                $table->dropColumn('ai_hint');
            });
        }
    }
};
