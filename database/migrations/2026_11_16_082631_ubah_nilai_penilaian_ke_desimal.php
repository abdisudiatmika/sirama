<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The official SK lists results with two decimals (e.g. 83.90), and the
 * letter grade depends on them (80.93 is AB, 81 is A), so scores keep
 * two decimals instead of being rounded to whole numbers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->decimal('nilai', 5, 2)->unsigned()->change();
            $table->decimal('nilai_akhir', 5, 2)->unsigned()->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('penilaian', function (Blueprint $table) {
            $table->unsignedTinyInteger('nilai')->change();
            $table->unsignedTinyInteger('nilai_akhir')->nullable()->change();
        });
    }
};
