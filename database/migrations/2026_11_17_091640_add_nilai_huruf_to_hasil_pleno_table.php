<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The letter grade fixed by the Director's SK. When set, it is shown instead
 * of the letter computed from the score, since some SKs grade with a
 * different table (e.g. 64.33 as BC).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hasil_pleno', function (Blueprint $table) {
            $table->string('nilai_huruf', 2)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('hasil_pleno', function (Blueprint $table) {
            $table->dropColumn('nilai_huruf');
        });
    }
};
