<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempt_solution', function (Blueprint $table) {
            if (!Schema::hasColumn('attempt_solution', 'knowledge_traced_at')) {
                $table->timestamp('knowledge_traced_at')->nullable()->after('prompt_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attempt_solution', function (Blueprint $table) {
            if (Schema::hasColumn('attempt_solution', 'knowledge_traced_at')) {
                $table->dropColumn('knowledge_traced_at');
            }
        });
    }
};
