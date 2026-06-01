<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE lesson_block MODIFY type VARCHAR(50) NOT NULL');
            return;
        }

        Schema::table('lesson_block', function (Blueprint $table) {
            $table->string('type', 50)->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lesson_block MODIFY type ENUM('text','executableCode','quiz','divider','video','infoBox') NOT NULL");
        }
    }
};
