<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contest', function (Blueprint $table) {
            if (!Schema::hasColumn('contest', 'duration_minutes')) {
                $table->unsignedInteger('duration_minutes')->default(120)->after('starts_at');
            }
        });

        Schema::create('contest_participant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained('contest')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['contest_id', 'user_id']);
        });

        Schema::table('attempt_solution', function (Blueprint $table) {
            if (!Schema::hasColumn('attempt_solution', 'contest_id')) {
                $table->foreignId('contest_id')
                    ->nullable()
                    ->after('task_id')
                    ->constrained('contest')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attempt_solution', function (Blueprint $table) {
            if (Schema::hasColumn('attempt_solution', 'contest_id')) {
                $table->dropConstrainedForeignId('contest_id');
            }
        });

        Schema::dropIfExists('contest_participant');

        Schema::table('contest', function (Blueprint $table) {
            if (Schema::hasColumn('contest', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });
    }
};
